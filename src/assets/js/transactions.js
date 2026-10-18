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

    let form, list;

    function notice(id, text, type = 'danger') {
        const element = $(id);

        if (!element) {
            return;
        }

        element.textContent = text || '';
        element.className = text ? 'alert alert-' + type : 'alert d-none';
    }

    function categoryOf(tx) {
        return s.categories.find(
            category => String(category.id) === String(tx.category_id)
        );
    }

    /**
     * Build the URL for one server-side page.
     *
     * Example:
     * /api/transactions?page=2&per_page=10
     */
    function pageUrl(url, page) {
        const currentPage = Math.max(1, Number(page) || 1);

        return url +
            '?page=' + encodeURIComponent(currentPage) +
            '&per_page=' + PAGE_SIZE;
    }

    /**
     * Request one page and handle the case where the requested page
     * no longer exists, for example after deleting the last item on it.
     */
    async function getPage(url, page) {
        let requestedPage = Math.max(1, Number(page) || 1);

        async function requestPage(pageNumber) {
            const response = await api().get(
                pageUrl(url, pageNumber)
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

        const totalPages = Number(response.pagination.total_pages);

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

    async function reloadActive(requestId = null) {
        const currentRequestId = requestId === null
            ? ++s.requests.active
            : requestId;

        const requestedPage = s.page.active;

        const [transactions, categories] = await Promise.all([
            getPage(s.url, requestedPage),
            api().get(s.categoryUrl)
        ]);

        // Ignore an older response if a newer page was requested.
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

        list.empty('active', 'در حال دریافت تراکنش‌ها...');

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
                error.message + ' برای تلاش دوباره صفحه را تازه‌سازی کنید.'
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

            list.render('deleted', s.pagination.deleted);
        } catch (error) {
            if (requestId !== s.requests.deleted) {
                return;
            }

            s.deletedLoaded = false;

            list.empty('deleted', error.message, true);

            notice('transactionsMessage', error.message);

            throw error;
        } finally {
            if (requestId === s.requests.deleted) {
                s.loadingDeleted = false;
            }
        }
    }

    /**
     * Called by the existing pagination controls.
     * The table's visual structure remains unchanged.
     */
    function changePage(kind, page) {
        if (s.busy) {
            return;
        }

        if (
            kind !== 'active' &&
            kind !== 'deleted'
        ) {
            return;
        }

        s.page[kind] = Math.max(1, Number(page) || 1);

        if (kind === 'active') {
            loadActive();
        } else {
            loadDeleted().catch(() => { });
        }
    }

    function askDelete(tx) {
        s.deletingId = String(tx.id);

        $('deleteTransactionTitle').textContent = tx.title || '';

        notice('deleteTransactionMessage', '');

        w.bootstrap.Modal
            .getOrCreateInstance($('deleteTransactionModal'))
            .show();
    }

    async function remove() {
        if (s.busy || !s.deletingId) {
            return;
        }

        const button = $('confirmDeleteTransactionButton');

        s.busy = true;
        button.disabled = true;

        try {
            const result = await api().post(
                s.url + '/' +
                encodeURIComponent(s.deletingId) +
                '/delete'
            );

            w.bootstrap.Modal
                .getOrCreateInstance($('deleteTransactionModal'))
                .hide();

            notice(
                'transactionsMessage',
                result.message || 'تراکنش حذف شد.',
                'success'
            );

            s.deletedLoaded = false;

            try {
                // Keep the current page if it still exists.
                // reloadActive() handles an out-of-range page.
                await reloadActive();
            } catch (error) {
                notice(
                    'transactionsMessage',
                    'حذف شد؛ اما فهرست به‌روز نشد: ' + error.message,
                    'warning'
                );
            }
        } catch (error) {
            notice('deleteTransactionMessage', error.message);
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
                updates.some(item => item.status === 'rejected')
            ) {
                notice(
                    'transactionsMessage',
                    'بازیابی شد؛ اما دریافت دوباره اطلاعات ناقص ماند.',
                    'warning'
                );
            }
        } catch (error) {
            notice('transactionsMessage', error.message);
        } finally {
            s.busy = false;

            if (s.deletedLoaded) {
                list.render('deleted', s.pagination.deleted);
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
                deleted ? 'btn-outline-primary' : 'btn-primary'
            );

        $('showDeletedTransactionsButton').className =
            'btn ' + (
                deleted ? 'btn-primary' : 'btn-outline-primary'
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

        s.url = page.dataset.transactionsApiUrl.replace(/\/$/, '');
        s.categoryUrl = page.dataset.categoriesApiUrl.replace(/\/$/, '');

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

        $('addTransactionButton').addEventListener('click', () => {
            if (!s.categoriesLoaded) {
                notice(
                    'transactionsMessage',
                    'دسته‌بندی‌ها هنوز دریافت نشده‌اند.'
                );
            } else {
                form.open();
            }
        });

        $('confirmDeleteTransactionButton')
            .addEventListener('click', remove);

        $('showActiveTransactionsButton')
            .addEventListener('click', () => switchTab(false));

        $('showDeletedTransactionsButton')
            .addEventListener('click', () => switchTab(true));

        $('deleteTransactionModal').addEventListener(
            'hidden.bs.modal',
            () => notice('deleteTransactionMessage', '')
        );

        switchTab(false);
        loadActive();
    }

    if (d.readyState === 'loading') {
        d.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }

})(window, document);