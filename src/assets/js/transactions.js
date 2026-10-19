/** TARAZINO | Transactions page coordinator. Load this AFTER form and list. */
(function (w, d) {
    'use strict';

    const $ = id => d.getElementById(id);
    const fmt = () => w.PersianFormat;
    const api = () => w.TarazinoApi;

    const PAGE_SIZE = 10;

    const s = {
        url: '',
        categoryUrl: '',
        active: [],
        deleted: [],
        categories: [],

        pagination: {
            active: null,
            deleted: null
        },

        filters: {
            search: '',
            type: 'all',
            category_id: 'all',
            date_from: '',
            date_to: '',
            sort: 'newest'
        },

        categoriesLoaded: false,
        deletedLoaded: false,
        loadingDeleted: false,

        page: {
            active: 1,
            deleted: 1
        },

        requests: {
            active: 0,
            deleted: 0
        },

        busy: false,
        deletingId: null
    };

    let form;
    let list;

    function notice(id, text, type = 'danger') {
        const element = $(id);

        if (!element) {
            return;
        }

        element.textContent = text || '';
        element.className = text
            ? 'alert alert-' + type
            : 'alert d-none';
    }

    function categoryOf(tx) {
        return s.categories.find(
            category =>
                String(category.id) === String(tx.category_id)
        );
    }

    /**
     * Build a URL using the current page and applied filters.
     * "all" means no filter and must not be sent as an actual filter value.
     */
    function pageUrl(url, page, filters = null) {
        const params = new URLSearchParams();

        params.set(
            'page',
            String(Math.max(1, Number(page) || 1))
        );

        params.set('per_page', String(PAGE_SIZE));

        if (filters) {
            Object.entries(filters).forEach(([key, value]) => {
                if (
                    value === '' ||
                    value === null ||
                    value === undefined
                ) {
                    return;
                }

                if (
                    (key === 'type' || key === 'category_id') &&
                    value === 'all'
                ) {
                    return;
                }

                params.set(key, String(value));
            });
        }

        return url + '?' + params.toString();
    }

    async function getPage(url, page, filters = null) {
        let requestedPage = Math.max(
            1,
            Number(page) || 1
        );

        async function requestPage(pageNumber) {
            const response = await api().get(
                pageUrl(url, pageNumber, filters)
            );

            if (
                !response ||
                !Array.isArray(response.transactions) ||
                !response.pagination ||
                typeof response.pagination !== 'object'
            ) {
                throw new Error(
                    'ساختار پاسخ صفحه‌بندی تراکنش‌ها معتبر نیست.'
                );
            }

            return response;
        }

        let response = await requestPage(requestedPage);

        const totalPages = Number(
            response.pagination.total_pages
        );

        if (!Number.isFinite(totalPages) || totalPages < 0) {
            throw new Error(
                'اطلاعات صفحه‌بندی دریافتی از سرور معتبر نیست.'
            );
        }

        const lastAvailablePage = Math.max(1, totalPages);

        if (requestedPage > lastAvailablePage) {
            requestedPage = lastAvailablePage;
            response = await requestPage(requestedPage);
        }

        return response;
    }

    /**
     * Read the filters currently selected in the form.
     */
    function readFilters() {
        return {
            search: $('transactionSearch').value.trim(),
            type: $('transactionTypeFilter').value,
            category_id: $('transactionCategoryFilter').value,
            date_from: $('transactionDateFrom').value,
            date_to: $('transactionDateTo').value,
            sort: $('transactionSort').value
        };
    }

    /**
     * Apply filters only after the user clicks the Apply button.
     */
    async function applyFilters() {
        s.filters = readFilters();
        s.page.active = 1;

        await loadActive();
    }

    /**
     * Reset all filters and return to the first page.
     */
    async function clearFilters() {
        $('transactionSearch').value = '';
        $('transactionTypeFilter').value = 'all';
        $('transactionCategoryFilter').value = 'all';
        $('transactionDateFrom').value = '';
        $('transactionDateTo').value = '';
        $('transactionSort').value = 'newest';

        s.filters = {
            search: '',
            type: 'all',
            category_id: 'all',
            date_from: '',
            date_to: '',
            sort: 'newest'
        };

        s.page.active = 1;

        list.categories();

        await loadActive();
    }

    async function reloadActive(requestId = null) {
        const currentRequestId = requestId === null
            ? ++s.requests.active
            : requestId;

        const requestedPage = s.page.active;

        const [transactions, categories] = await Promise.all([
            getPage(s.url, requestedPage, s.filters),
            api().get(s.categoryUrl)
        ]);

        // Ignore responses from outdated requests.
        if (currentRequestId !== s.requests.active) {
            return;
        }

        if (!Array.isArray(categories.categories)) {
            throw new Error(
                'ساختار پاسخ دسته‌بندی‌ها معتبر نیست.'
            );
        }

        s.active = transactions.transactions;
        s.pagination.active = transactions.pagination;

        s.page.active = Math.max(
            1,
            Number(transactions.pagination.current_page) || 1
        );

        s.categories = categories.categories;
        s.categoriesLoaded = true;

        $('addTransactionButton').disabled = false;

        list.categories();
        list.render('active', s.pagination.active);
    }

    async function loadActive() {
        const requestId = ++s.requests.active;

        list.empty(
            'active',
            'در حال دریافت تراکنش‌ها...'
        );

        try {
            await reloadActive(requestId);
        } catch (error) {
            if (requestId !== s.requests.active) {
                return;
            }

            s.categoriesLoaded = false;

            $('addTransactionButton').disabled = true;

            list.empty(
                'active',
                'دریافت تراکنش‌ها انجام نشد.',
                true
            );

            notice(
                'transactionsMessage',
                error.message +
                ' برای تلاش دوباره صفحه را تازه‌سازی کنید.'
            );
        }
    }

    async function loadDeleted() {
        if (s.loadingDeleted) {
            return;
        }

        const requestId = ++s.requests.deleted;

        s.loadingDeleted = true;

        list.empty(
            'deleted',
            'در حال دریافت تراکنش‌های حذف‌شده...'
        );

        try {
            const response = await getPage(
                s.url + '/deleted',
                s.page.deleted
            );

            if (requestId !== s.requests.deleted) {
                return;
            }

            s.deleted = response.transactions;
            s.pagination.deleted = response.pagination;

            s.page.deleted = Math.max(
                1,
                Number(response.pagination.current_page) || 1
            );

            s.deletedLoaded = true;

            list.render(
                'deleted',
                s.pagination.deleted
            );
        } catch (error) {
            if (requestId !== s.requests.deleted) {
                return;
            }

            s.deletedLoaded = false;

            list.empty(
                'deleted',
                error.message,
                true
            );

            notice(
                'transactionsMessage',
                error.message
            );

            throw error;
        } finally {
            if (requestId === s.requests.deleted) {
                s.loadingDeleted = false;
            }
        }
    }

    /**
     * Load another page while keeping the currently applied filters.
     */
    function changePage(kind, page) {
        if (s.busy) {
            return;
        }

        if (kind !== 'active' && kind !== 'deleted') {
            return;
        }

        s.page[kind] = Math.max(
            1,
            Number(page) || 1
        );

        if (kind === 'active') {
            loadActive();
        } else {
            loadDeleted().catch(() => { });
        }
    }

    function askDelete(tx) {
        s.deletingId = String(tx.id);

        $('deleteTransactionTitle').textContent =
            tx.title || '';

        notice('deleteTransactionMessage', '');

        w.bootstrap.Modal
            .getOrCreateInstance(
                $('deleteTransactionModal')
            )
            .show();
    }

    async function remove() {
        if (s.busy || !s.deletingId) {
            return;
        }

        const button = $(
            'confirmDeleteTransactionButton'
        );

        s.busy = true;
        button.disabled = true;

        try {
            const result = await api().post(
                s.url + '/' +
                encodeURIComponent(s.deletingId) +
                '/delete'
            );

            w.bootstrap.Modal
                .getOrCreateInstance(
                    $('deleteTransactionModal')
                )
                .hide();

            notice(
                'transactionsMessage',
                result.message || 'تراکنش حذف شد.',
                'success'
            );

            s.deletedLoaded = false;

            const refreshes = await Promise.allSettled([
                reloadActive(),
                loadDeleted()
            ]);

            if (refreshes.some(item => item.status === 'rejected')) {
                notice(
                    'transactionsMessage',
                    'حذف شد؛ اما یکی از فهرست‌ها به‌روز نشد.',
                    'warning'
                );
            }
        } catch (error) {
            notice(
                'deleteTransactionMessage',
                error.message
            );
        } finally {
            s.busy = false;
            s.deletingId = null;
            button.disabled = false;
        }
    }

    async function restore(id, button) {
        if (
            s.busy ||
            !w.confirm(
                'آیا تراکنش بازیابی شود؟ تغییرات مالی توسط سرور محاسبه می‌شوند.'
            )
        ) {
            return;
        }

        s.busy = true;
        button.disabled = true;

        try {
            const result = await api().post(
                s.url + '/' +
                encodeURIComponent(id) +
                '/restore'
            );

            notice(
                'transactionsMessage',
                result.message || 'تراکنش بازیابی شد.',
                'success'
            );

            s.deletedLoaded = false;

            const updates = await Promise.allSettled([
                reloadActive(),
                loadDeleted()
            ]);

            if (
                updates.some(
                    item => item.status === 'rejected'
                )
            ) {
                notice(
                    'transactionsMessage',
                    'بازیابی شد؛ اما دریافت دوباره اطلاعات ناقص ماند.',
                    'warning'
                );
            }
        } catch (error) {
            notice(
                'transactionsMessage',
                error.message
            );
        } finally {
            s.busy = false;

            if (s.deletedLoaded) {
                list.render(
                    'deleted',
                    s.pagination.deleted
                );
            }
        }
    }

    function switchTab(deleted) {
        $('activeTransactionsPanel').hidden = deleted;
        $('deletedTransactionsPanel').hidden = !deleted;

        $('activeTransactionsPanel')
            .classList.toggle('d-none', deleted);

        $('deletedTransactionsPanel')
            .classList.toggle('d-none', !deleted);

        $('showActiveTransactionsButton').className =
            'btn ' + (
                deleted
                    ? 'btn-outline-primary'
                    : 'btn-primary'
            );

        $('showDeletedTransactionsButton').className =
            'btn ' + (
                deleted
                    ? 'btn-primary'
                    : 'btn-outline-primary'
            );

        $('showActiveTransactionsButton').setAttribute(
            'aria-pressed',
            String(!deleted)
        );

        $('showDeletedTransactionsButton').setAttribute(
            'aria-pressed',
            String(deleted)
        );

        if (deleted && !s.deletedLoaded) {
            loadDeleted().catch(() => { });
        }
    }

    function initialize() {
        const page = $('transactionsMain');

        if (!page) {
            return;
        }

        if (
            !api() ||
            !fmt() ||
            !w.bootstrap ||
            !w.TarazinoTransactionForm ||
            !w.TarazinoTransactionList
        ) {
            notice(
                'transactionsMessage',
                'یکی از فایل‌های مشترک تراکنش‌ها بارگذاری نشده است.'
            );

            return;
        }

        s.url = page.dataset.transactionsApiUrl
            .replace(/\/$/, '');

        s.categoryUrl = page.dataset.categoriesApiUrl
            .replace(/\/$/, '');

        const ctx = {
            s,
            $,
            fmt,
            api,
            notice,
            categoryOf,
            reloadActive,
            restore,
            changePage,
            edit: tx => form.open(tx),
            askDelete
        };

        form = w.TarazinoTransactionForm.create(ctx);
        list = w.TarazinoTransactionList.create(ctx);

        form.bind();
        list.bind();

        $('addTransactionButton').addEventListener(
            'click',
            () => {
                if (!s.categoriesLoaded) {
                    notice(
                        'transactionsMessage',
                        'دسته‌بندی‌ها هنوز دریافت نشده‌اند.'
                    );
                } else {
                    form.open();
                }
            }
        );

        $('confirmDeleteTransactionButton')
            .addEventListener('click', remove);

        $('showActiveTransactionsButton')
            .addEventListener(
                'click',
                () => switchTab(false)
            );

        $('showDeletedTransactionsButton')
            .addEventListener(
                'click',
                () => switchTab(true)
            );

        $('deleteTransactionModal').addEventListener(
            'hidden.bs.modal',
            () => notice('deleteTransactionMessage', '')
        );

        const applyButton = $(
            'applyTransactionFiltersButton'
        );

        const clearButton = $(
            'clearTransactionFiltersButton'
        );

        if (applyButton) {
            applyButton.addEventListener(
                'click',
                applyFilters
            );
        }

        if (clearButton) {
            clearButton.addEventListener(
                'click',
                clearFilters
            );
        }

        switchTab(false);
        loadActive();
    }

    if (d.readyState === 'loading') {
        d.addEventListener(
            'DOMContentLoaded',
            initialize,
            { once: true }
        );
    } else {
        initialize();
    }

})(window, document);
