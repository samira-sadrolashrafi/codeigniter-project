/** TARAZINO | Categories coordinator: API, filters, pagination, delete/restore. */
(function (w, d) {
    'use strict';

    const $ = id => d.getElementById(id);
    const api = () => w.TarazinoApi;
    const fmt = () => w.PersianFormat;
    const PAGE_SIZE = 10;

    const DEFAULT_FILTERS = {
        search: '',
        type: 'all',
        sort: 'newest'
    };

    const s = {
        apiUrl: '',
        active: [],
        deleted: [],
        page: { active: 1, deleted: 1 },
        activeRequest: 0,
        pagination: { active: null, deleted: null },
        perPage: PAGE_SIZE,
        appliedFilters: { ...DEFAULT_FILTERS },
        busy: false,
        deletingId: null,
        deletedLoaded: false,
        loadingDeleted: false
    };

    let form, list;

    function notice(id, text, type = 'danger') {
        const element = $(id);
        if (!element) return;

        element.textContent = text || '';
        element.className = text
            ? 'alert alert-' + type
            : 'alert d-none';

        if (id === 'deleteCategoryMessage') {
            element.classList.add('mt-3', 'mb-0');
        }
    }

    /**
     * ساخت URL دسته‌بندی‌های فعال با فیلترهای اعمال‌شده.
     * مقادیر این بخش تا زمان کلیک روی «اعمال فیلترها»
     * از ورودی‌های صفحه خوانده نمی‌شوند.
     */
    function pageUrl(page) {
        const params = new URLSearchParams();

        params.set('page', String(page));
        params.set('per_page', String(s.perPage));
        params.set('search', s.appliedFilters.search);
        params.set('type', s.appliedFilters.type);
        params.set('sort', s.appliedFilters.sort);

        return s.apiUrl + '?' + params.toString();
    }

    /**
     * خواندن فیلترهای فرم و اعمال آن‌ها روی فهرست فعال.
     */
    async function applyFilters() {
        if (s.busy) return;

        const searchInput = $('categoriesSearch');
        const typeInput = $('categoriesTypeFilter');
        const sortInput = $('categoriesSortFilter');

        const searchValue = searchInput
            ? searchInput.value.trim()
            : '';

        const typeValue = typeInput
            ? typeInput.value
            : 'all';

        const sortValue = sortInput
            ? sortInput.value
            : 'newest';

        s.appliedFilters = {
            search: searchValue,
            type: ['income', 'expense'].includes(typeValue)
                ? typeValue
                : 'all',
            sort: ['newest', 'oldest'].includes(sortValue)
                ? sortValue
                : 'newest'
        };

        // هر بار اعمال فیلتر، از صفحه اول شروع می‌کنیم.
        s.page.active = 1;

        notice('categoriesMessage', '');

        await loadActive();
    }

    /**
     * پاک کردن ورودی‌ها و بازگرداندن فیلترها به حالت پیش‌فرض.
     */
    async function clearFilters() {
        if (s.busy) return;

        const searchInput = $('categoriesSearch');
        const typeInput = $('categoriesTypeFilter');
        const sortInput = $('categoriesSortFilter');

        if (searchInput) {
            searchInput.value = '';
        }

        if (typeInput) {
            typeInput.value = 'all';
        }

        if (sortInput) {
            sortInput.value = 'newest';
        }

        s.appliedFilters = { ...DEFAULT_FILTERS };
        s.page.active = 1;

        notice('categoriesMessage', '');

        await loadActive();
    }

    async function reloadActive(page = s.page.active) {
        const requestId = ++s.activeRequest;
        let result;

        try {
            result = await api().get(pageUrl(page));
        } catch (error) {
            if (requestId !== s.activeRequest) return;
            throw error;
        }

        // از جایگزین شدن نتیجه درخواست‌های قدیمی جلوگیری می‌کنیم.
        if (requestId !== s.activeRequest) return;

        if (
            !Array.isArray(result.categories) ||
            !result.pagination ||
            typeof result.pagination.total_pages !== 'number'
        ) {
            throw new Error('فهرست دسته‌بندی‌ها معتبر نیست.');
        }

        const pagination = result.pagination;

        // اگر صفحه درخواست‌شده دیگر وجود نداشته باشد،
        // به آخرین صفحه معتبر برمی‌گردیم.
        if (
            pagination.total_pages > 0 &&
            page > pagination.total_pages
        ) {
            return reloadActive(pagination.total_pages);
        }

        s.active = result.categories;
        s.pagination.active = pagination;

        s.page.active = pagination.total_pages > 0
            ? pagination.current_page
            : 1;

        list.render('active');
    }

    async function loadActive() {
        list.empty('active', 'در حال دریافت دسته‌بندی‌ها...');

        try {
            await reloadActive();
        } catch (error) {
            list.empty(
                'active',
                error.message || 'دریافت دسته‌بندی‌ها انجام نشد.',
                true,
                loadActive
            );

            notice(
                'categoriesMessage',
                error.message || 'دریافت دسته‌بندی‌ها انجام نشد.'
            );
        }
    }

    async function loadDeleted(page = s.page.deleted) {
        if (s.loadingDeleted) return;

        s.loadingDeleted = true;
        list.empty('deleted', 'در حال دریافت دسته‌بندی‌های حذف‌شده...');

        try {
            const result = await api().get(
                s.apiUrl + '/deleted'
                + '?page=' + encodeURIComponent(page)
                + '&per_page=' + encodeURIComponent(s.perPage)
            );

            if (
                !Array.isArray(result.categories) ||
                !result.pagination ||
                typeof result.pagination.total_pages !== 'number'
            ) {
                throw new Error('فهرست حذف‌شده‌ها معتبر نیست.');
            }

            const pagination = result.pagination;

            if (
                pagination.total_pages > 0 &&
                page > pagination.total_pages
            ) {
                // پیش از درخواست صفحه معتبر بعدی، قفل را آزاد می‌کنیم.
                s.loadingDeleted = false;
                return loadDeleted(pagination.total_pages);
            }

            s.deleted = result.categories;
            s.pagination.deleted = pagination;

            s.page.deleted = pagination.total_pages > 0
                ? pagination.current_page
                : 1;

            for (const category of s.deleted) {
                category.restore_available =
                    !!category.restore_until &&
                    new Date(category.restore_until).getTime() > Date.now();
            }

            s.deletedLoaded = true;
            list.render('deleted');
        } catch (error) {
            s.deletedLoaded = false;

            list.empty(
                'deleted',
                error.message || 'دریافت انجام نشد.',
                true,
                () => loadDeleted()
            );

            notice(
                'categoriesMessage',
                error.message || 'دریافت دسته‌بندی‌های حذف‌شده انجام نشد.'
            );

            throw error;
        } finally {
            s.loadingDeleted = false;
        }
    }

    async function changePage(kind, page) {
        if (s.busy) return;

        if (kind === 'deleted' && s.loadingDeleted) return;

        const pagination = s.pagination[kind];

        if (!pagination) return;

        if (
            page < 1 ||
            page > pagination.total_pages ||
            page === s.page[kind]
        ) {
            return;
        }

        const previousPage = s.page[kind];
        s.page[kind] = page;

        try {
            if (kind === 'active') {
                // pageUrl فیلترهای اعمال‌شده را حفظ می‌کند.
                await reloadActive(page);
            } else {
                await loadDeleted(page);
            }
        } catch (error) {
            s.page[kind] = previousPage;

            notice(
                'categoriesMessage',
                error.message || 'دریافت صفحه انجام نشد.'
            );
        }
    }

    function askDelete(category) {
        s.deletingId = String(category.id);

        $('deleteCategoryName').textContent = category.name || '';
        notice('deleteCategoryMessage', '');

        w.bootstrap.Modal
            .getOrCreateInstance($('deleteCategoryModal'))
            .show();
    }

    async function remove() {
        if (s.busy || s.deletingId === null) return;

        const button = $('confirmDeleteCategoryButton');

        s.busy = true;
        button.disabled = true;
        button.textContent = 'در حال حذف...';

        try {
            const result = await api().post(
                s.apiUrl + '/' + encodeURIComponent(s.deletingId) + '/delete'
            );

            w.bootstrap.Modal
                .getOrCreateInstance($('deleteCategoryModal'))
                .hide();

            notice(
                'categoriesMessage',
                result.message || 'دسته‌بندی حذف شد.',
                'success'
            );

            s.deletedLoaded = false;

            try {
                // فیلترهای اعمال‌شده هنگام تازه‌سازی حفظ می‌شوند.
                await reloadActive();
            } catch (error) {
                notice(
                    'categoriesMessage',
                    'حذف شد، اما فهرست به‌روز نشد: ' + error.message,
                    'warning'
                );
            }
        } catch (error) {
            notice(
                'deleteCategoryMessage',
                error.message || 'حذف انجام نشد.'
            );
        } finally {
            s.busy = false;
            s.deletingId = null;
            button.disabled = false;
            button.textContent = 'حذف دسته‌بندی';
        }
    }

    async function restore(id, button) {
        if (
            s.busy ||
            !w.confirm(
                'دسته‌بندی بازیابی شود؟ تراکنش‌های حذف‌شده خودکار بازیابی نمی‌شوند.'
            )
        ) {
            return;
        }

        s.busy = true;
        button.disabled = true;
        button.textContent = 'در حال بازیابی...';

        try {
            const result = await api().post(
                s.apiUrl + '/' + encodeURIComponent(id) + '/restore'
            );

            s.deletedLoaded = false;

            const updates = await Promise.allSettled([
                reloadActive(),
                loadDeleted()
            ]);

            const failed = updates.some(
                item => item.status === 'rejected'
            );

            notice(
                'categoriesMessage',
                failed
                    ? 'دسته‌بندی بازیابی شد؛ اما تازه‌سازی یکی از فهرست‌ها انجام نشد.'
                    : (result.message || 'دسته‌بندی بازیابی شد.'),
                failed ? 'warning' : 'success'
            );
        } catch (error) {
            notice(
                'categoriesMessage',
                error.message || 'بازیابی انجام نشد.'
            );
        } finally {
            s.busy = false;

            if (s.deletedLoaded) {
                list.render('deleted');
            } else {
                button.disabled = false;
                button.textContent = 'بازیابی';
            }
        }
    }

    function switchPanel(deleted) {
        $('activeCategoriesPanel').hidden = deleted;
        $('deletedCategoriesPanel').hidden = !deleted;

        $('activeCategoriesPanel').classList.toggle('d-none', deleted);
        $('deletedCategoriesPanel').classList.toggle('d-none', !deleted);

        $('showActiveCategoriesButton').className =
            'btn ' + (deleted ? 'btn-outline-primary' : 'btn-primary');

        $('showDeletedCategoriesButton').className =
            'btn ' + (deleted ? 'btn-primary' : 'btn-outline-primary');

        $('showActiveCategoriesButton').setAttribute(
            'aria-pressed',
            String(!deleted)
        );

        $('showDeletedCategoriesButton').setAttribute(
            'aria-pressed',
            String(deleted)
        );

        if (deleted && !s.deletedLoaded) {
            loadDeleted().catch(() => { });
        }
    }

    function initialize() {
        const page = $('categoriesMain');
        if (!page) return;

        if (
            !api() ||
            !fmt() ||
            !w.bootstrap ||
            !w.TarazinoCategoryForm ||
            !w.TarazinoCategoryList
        ) {
            notice(
                'categoriesMessage',
                'یکی از اسکریپت‌های مشترک دسته‌بندی‌ها بارگذاری نشده است.'
            );
            return;
        }

        s.apiUrl = page.dataset.categoriesApiUrl.replace(/\/$/, '');

        const ctx = {
            s,
            $,
            api,
            fmt,
            notice,
            reloadActive,
            changePage,
            applyFilters,
            clearFilters,
            edit: category => form.openEdit(category),
            askDelete,
            restore
        };

        form = w.TarazinoCategoryForm.create(ctx);
        list = w.TarazinoCategoryList.create(ctx);

        form.bind();
        list.bind();

        $('confirmDeleteCategoryButton').addEventListener('click', remove);

        $('showActiveCategoriesButton').addEventListener(
            'click',
            () => switchPanel(false)
        );

        $('showDeletedCategoriesButton').addEventListener(
            'click',
            () => switchPanel(true)
        );

        $('deleteCategoryModal').addEventListener(
            'hidden.bs.modal',
            () => notice('deleteCategoryMessage', '')
        );

        switchPanel(false);
        loadActive();
    }

    if (d.readyState === 'loading') {
        d.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})(window, document);