/** TARAZINO | Render active/deleted transaction tables and server-side pagination. */
(function (w, d) {
    'use strict';

    function create(ctx) {
        const {
            s, $, fmt, categoryOf, restore, edit, askDelete, changePage
        } = ctx;

        const size = 10;
        const paginationData = {
            active: null,
            deleted: null
        };

        function empty(kind, text, error = false) {
            const id = kind === 'active'
                ? 'transactionsTableBody'
                : 'deletedTransactionsTableBody';

            const body = $(id);
            body.replaceChildren();

            const cell = body.insertRow().insertCell();
            cell.colSpan = kind === 'active' ? 7 : 6;
            cell.className = 'text-center py-5 ' +
                (error ? 'text-danger' : 'text-body-secondary');
            cell.textContent = text;

            const prefix = kind === 'active'
                ? 'transactions'
                : 'deletedTransactions';

            $(prefix + 'Pagination').classList.add('d-none');
        }

        function cell(row, text, style = '') {
            const td = row.insertCell();
            td.textContent = text == null ? '' : text;
            td.className = style;
            return td;
        }

        function button(parent, label, action, id, style) {
            const btn = d.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-' + style + ' me-1';
            btn.textContent = label;
            btn.dataset.action = action;
            btn.dataset.id = String(id);

            parent.append(btn);
            return btn;
        }

        /**
         * The backend already returns the requested page.
         * Do not use slice() here.
         */
        function paginate(items, kind, draw, pagination) {
            if (pagination) {
                paginationData[kind] = pagination;
            }

            const fallback = {
                current_page: 1,
                per_page: size,
                total_items: items.length,
                total_pages: Math.ceil(items.length / size)
            };

            const info = paginationData[kind] || fallback;

            const perPage = Math.max(
                1,
                Number(info.per_page) || size
            );

            const totalItems = Math.max(
                0,
                Number(info.total_items) || 0
            );

            const totalPagesValue = Number(info.total_pages);

            const totalPages = Number.isFinite(totalPagesValue)
                ? Math.max(0, totalPagesValue)
                : Math.ceil(totalItems / perPage);

            const currentPage = Math.max(
                1,
                Number(info.current_page) || Number(s.page[kind]) || 1
            );

            s.page[kind] = currentPage;

            // These items are already limited by the backend.
            draw(items);

            const prefix = kind === 'active'
                ? 'transactions'
                : 'deletedTransactions';

            const paginationElement = $(prefix + 'Pagination');

            paginationElement.classList.toggle(
                'd-none',
                totalPages <= 1
            );

            const from = totalItems === 0
                ? 0
                : ((currentPage - 1) * perPage) + 1;

            const to = Math.min(currentPage * perPage, totalItems);

            $(prefix + 'PaginationSummary').textContent = totalItems
                ? `نمایش ${fmt().integer(from)} تا ${fmt().integer(to)} از ${fmt().integer(totalItems)}`
                : 'موردی وجود ندارد';

            const list = $(prefix + 'PaginationList');
            list.replaceChildren();

            if (totalPages <= 1) {
                return;
            }

            function add(label, number, disabled, selected = false) {
                const li = d.createElement('li');
                const btn = d.createElement('button');

                li.className = 'page-item' +
                    (disabled ? ' disabled' : '') +
                    (selected ? ' active' : '');

                btn.type = 'button';
                btn.className = 'page-link';
                btn.textContent = label;
                btn.disabled = disabled;

                if (selected) {
                    btn.setAttribute('aria-current', 'page');
                }

                btn.addEventListener('click', () => {
                    if (
                        btn.disabled ||
                        number < 1 ||
                        number > totalPages ||
                        number === currentPage
                    ) {
                        return;
                    }

                    // Ask the coordinator to fetch this page from the API.
                    changePage(kind, number);
                });

                li.append(btn);
                list.append(li);
            }

            add(
                'قبلی',
                currentPage - 1,
                currentPage <= 1
            );

            const start = Math.max(
                1,
                Math.min(currentPage - 2, totalPages - 4)
            );

            for (
                let page = start;
                page <= Math.min(totalPages, start + 4);
                page++
            ) {
                add(
                    fmt().integer(page),
                    page,
                    false,
                    page === currentPage
                );
            }

            add(
                'بعدی',
                currentPage + 1,
                currentPage >= totalPages
            );
        }

        /**
         * Existing client-side filters are preserved.
         * They currently operate on the records loaded for the active page.
         */
        function filtered() {
            const search = $('transactionSearch')
                .value.trim().toLocaleLowerCase('fa-IR');

            const type = $('transactionTypeFilter').value;
            const categoryId = $('transactionCategoryFilter').value;

            const from = $('transactionDateFrom').value;
            const to = $('transactionDateTo').value;

            return s.active.filter(tx => {
                const cat = categoryOf(tx);

                if (
                    search &&
                    ![tx.title, tx.description].some(value =>
                        String(value || '')
                            .toLocaleLowerCase('fa-IR')
                            .includes(search)
                    )
                ) {
                    return false;
                }

                if (
                    type !== 'all' &&
                    (!cat || cat.type !== type)
                ) {
                    return false;
                }

                if (
                    categoryId !== 'all' &&
                    String(tx.category_id) !== categoryId
                ) {
                    return false;
                }

                if (from && tx.transaction_date < from) {
                    return false;
                }

                if (to && tx.transaction_date > to) {
                    return false;
                }

                return true;
            }).sort((a, b) =>
                String(b.transaction_date || '')
                    .localeCompare(String(a.transaction_date || '')) ||
                Number(b.id) - Number(a.id)
            );
        }

        function active(pagination) {
            if (pagination) {
                paginationData.active = pagination;
            }

            paginate(filtered(), 'active', items => {
                if (!items.length) {
                    empty('active', 'تراکنشی با این شرایط پیدا نشد.');
                    return;
                }

                const body = $('transactionsTableBody');
                body.replaceChildren();

                items.forEach(tx => {
                    const cat = categoryOf(tx);
                    const type = cat ? cat.type : '';
                    const row = body.insertRow();

                    cell(row, tx.title || '—');

                    cell(
                        row,
                        type === 'income'
                            ? 'درآمد'
                            : type === 'expense'
                                ? 'هزینه'
                                : 'نامشخص'
                    );

                    cell(
                        row,
                        fmt().number(tx.amount),
                        type === 'expense' ? 'text-danger' : 'text-success'
                    );

                    cell(row, cat ? cat.name : '—');

                    cell(
                        row,
                        fmt().date(tx.transaction_date),
                        'text-nowrap'
                    );

                    cell(row, tx.description || '—');

                    const actions = row.insertCell();
                    actions.className = 'text-nowrap';

                    button(actions, 'ویرایش', 'edit', tx.id, 'primary');
                    button(actions, 'حذف', 'delete', tx.id, 'danger');
                });
            }, paginationData.active);
        }

        function deleted(pagination) {
            if (pagination) {
                paginationData.deleted = pagination;
            }

            paginate(s.deleted, 'deleted', items => {
                if (!items.length) {
                    empty('deleted', 'تراکنش حذف‌شده‌ای وجود ندارد.');
                    return;
                }

                const body = $('deletedTransactionsTableBody');
                body.replaceChildren();

                items.forEach(tx => {
                    const row = body.insertRow();

                    cell(row, tx.title || '—');
                    cell(row, fmt().number(tx.amount));
                    cell(row, tx.category_name || '—');
                    cell(row, fmt().date(tx.transaction_date));
                    cell(row, fmt().dateTime(tx.deleted_at));

                    const actions = row.insertCell();

                    const allowed =
                        tx.restore_available === true &&
                        tx.category_deleted_at == null &&
                        !!tx.category_name;

                    const reason = !tx.restore_available
                        ? 'مهلت بازیابی تمام شده'
                        : (
                            tx.category_deleted_at != null ||
                            !tx.category_name
                        )
                            ? 'ابتدا دسته‌بندی مربوط را بازیابی کنید'
                            : 'قابل بازیابی';

                    cellStatus(actions, reason, allowed);

                    button(
                        actions,
                        'بازیابی',
                        'restore',
                        tx.id,
                        'success'
                    ).disabled = !allowed || s.busy;
                });
            }, paginationData.deleted);
        }

        function cellStatus(parent, text, okay) {
            const label = d.createElement('span');

            label.className = 'small d-block mb-2 ' +
                (okay ? 'text-success' : 'text-warning');

            label.textContent = text;
            parent.append(label);
        }

        function render(kind, pagination) {
            if (pagination) {
                paginationData[kind] = pagination;
            }

            if (kind === 'active') {
                active(paginationData.active);
            } else {
                deleted(paginationData.deleted);
            }
        }

        function categories() {
            const select = $('transactionCategoryFilter');
            const previous = select.value;
            const type = $('transactionTypeFilter').value;

            select.replaceChildren();

            [
                { id: 'all', name: 'همه دسته‌بندی‌ها' },
                ...s.categories.filter(c =>
                    type === 'all' || c.type === type
                )
            ].forEach(category => {
                const option = d.createElement('option');

                option.value = String(category.id);
                option.textContent = category.name;

                select.append(option);
            });

            if ([...select.options].some(option => option.value === previous)) {
                select.value = previous;
            }
        }

        function bind() {
            $('transactionsTableBody').addEventListener('click', event => {
                const btn = event.target.closest('button[data-action]');

                if (!btn) {
                    return;
                }

                const tx = s.active.find(
                    item => String(item.id) === btn.dataset.id
                );

                if (!tx) {
                    return;
                }

                if (btn.dataset.action === 'edit') {
                    edit(tx);
                }

                if (btn.dataset.action === 'delete') {
                    askDelete(tx);
                }
            });

            $('deletedTransactionsTableBody').addEventListener(
                'click',
                event => {
                    const btn = event.target.closest(
                        'button[data-action="restore"]'
                    );

                    if (btn && !btn.disabled) {
                        restore(btn.dataset.id, btn);
                    }
                }
            );

            // Keep the existing filter controls and their current behavior.
            [
                'transactionSearch',
                'transactionDateFrom',
                'transactionDateTo'
            ].forEach(id => {
                ['input', 'change'].forEach(type => {
                    $(id).addEventListener(type, () => {
                        active();
                    });
                });
            });

            $('transactionTypeFilter').addEventListener('change', () => {
                categories();
                active();
            });

            $('transactionCategoryFilter').addEventListener('change', () => {
                active();
            });
        }

        return {
            bind,
            render,
            empty,
            categories
        };
    }

    w.TarazinoTransactionList = Object.freeze({ create });

})(window, document);