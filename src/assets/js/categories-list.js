/** TARAZINO | Category tables, filters and server-side pagination. */
(function (w, d) {
    'use strict';

    function create(ctx) {
        const {
            s,
            $,
            fmt,
            edit,
            askDelete,
            restore,
            changePage
        } = ctx;

        const ids = kind => kind === 'active'
            ? ['categoriesTableBody', 'categoriesPagination']
            : ['deletedCategoriesTableBody', 'deletedCategoriesPagination'];

        function td(row, value, className = '') {
            const cell = row.insertCell();
            cell.textContent = value == null ? '' : value;
            cell.className = className;
            return cell;
        }

        function action(cell, text, actionName, id, color) {
            const button = d.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-outline-' + color + ' me-1';
            button.textContent = text;
            button.dataset.action = actionName;
            button.dataset.id = String(id);
            cell.appendChild(button);
            return button;
        }

        function empty(
            kind,
            text,
            isError = false,
            retry = null,
            hidePagination = true
        ) {
            const [bodyId, paginationId] = ids(kind);
            const body = $(bodyId);

            body.replaceChildren();

            const cell = body.insertRow().insertCell();
            cell.colSpan = kind === 'active' ? 4 : 5;
            cell.className =
                'text-center py-5 ' +
                (isError ? 'text-danger' : 'text-body-secondary');
            cell.textContent = text;

            if (hidePagination) {
                $(paginationId).classList.add('d-none');
            }

            if (retry) {
                const button = d.createElement('button');
                button.type = 'button';
                button.className =
                    'btn btn-sm btn-outline-primary ms-2';
                button.textContent = 'تلاش دوباره';
                button.addEventListener('click', retry);
                cell.appendChild(button);
            }
        }

        // فیلترهای فعلی حفظ شده‌اند و فعلاً روی صفحه دریافت‌شده اعمال می‌شوند.
        function filtered() {
            const query = $('categoriesSearch').value
                .trim()
                .toLocaleLowerCase('fa-IR');

            const type = $('categoriesTypeFilter').value;

            return s.active.filter(cat =>
                (!query ||
                    String(cat.name || '')
                        .toLocaleLowerCase('fa-IR')
                        .includes(query)) &&
                (type === 'all' || cat.type === type)
            );
        }

        function renderPagination(kind) {
            const [, prefix] = ids(kind);
            const pagination = s.pagination[kind];

            if (!pagination) {
                $(prefix).classList.add('d-none');
                return;
            }

            const currentPage = pagination.current_page;
            const totalPages = pagination.total_pages;
            const total = pagination.total;
            const perPage = pagination.per_page;

            $(prefix).classList.toggle('d-none', totalPages <= 1);

            const summary = $(prefix + 'Summary');

            if (total === 0) {
                summary.textContent = 'موردی وجود ندارد';
            } else {
                const start = (currentPage - 1) * perPage + 1;
                const end = Math.min(currentPage * perPage, total);

                summary.textContent =
                    `نمایش ${fmt().integer(start)} تا ` +
                    `${fmt().integer(end)} از ${fmt().integer(total)}`;
            }

            const pageList = $(prefix + 'List');
            pageList.replaceChildren();

            if (totalPages <= 1) return;

            function pageButton(text, page, disabled, selected = false) {
                const li = d.createElement('li');
                const button = d.createElement('button');

                li.className =
                    'page-item' +
                    (disabled ? ' disabled' : '') +
                    (selected ? ' active' : '');

                button.type = 'button';
                button.className = 'page-link';
                button.textContent = text;
                button.disabled = disabled;

                if (selected) {
                    button.setAttribute('aria-current', 'page');
                }

                button.addEventListener('click', () => {
                    changePage(kind, page);
                });

                li.appendChild(button);
                pageList.appendChild(li);
            }

            pageButton(
                'قبلی',
                currentPage - 1,
                currentPage === 1
            );

            const first = Math.max(
                1,
                Math.min(currentPage - 2, totalPages - 4)
            );

            for (
                let page = first;
                page <= Math.min(totalPages, first + 4);
                page++
            ) {
                pageButton(
                    fmt().integer(page),
                    page,
                    false,
                    page === currentPage
                );
            }

            pageButton(
                'بعدی',
                currentPage + 1,
                currentPage === totalPages
            );
        }

        // تنظیمات ظاهری دسته‌بندی‌ها؛ بدون ذخیره آیکون یا رنگ در دیتابیس.
        function visual(category) {
            const name = String(category.name || '');

            const presets = category.type === 'income'
                ? [
                    [/حقوق|دستمزد/, 'fa-money-bill-wave', 'text-success'],
                    [/فروش/, 'fa-cart-shopping', 'text-primary'],
                    [/هدیه/, 'fa-gift', 'text-info']
                ]
                : [
                    [/خوراک|غذا/, 'fa-utensils', 'text-warning'],
                    [/حمل|رفت.*آمد/, 'fa-car', 'text-info'],
                    [/مسکن|اجاره/, 'fa-house', 'text-primary'],
                    [/درمان|سلامت/, 'fa-heart-pulse', 'text-danger'],
                    [/آموزش/, 'fa-graduation-cap', 'text-info']
                ];

            const match = presets.find(([pattern]) => pattern.test(name));

            return match
                ? match.slice(1)
                : category.type === 'income'
                    ? ['fa-arrow-trend-up', 'text-success']
                    : ['fa-arrow-trend-down', 'text-danger'];
        }

        function render(kind) {
            const items = kind === 'active'
                ? filtered()
                : s.deleted;

            if (!items.length) {
                empty(
                    kind,
                    kind === 'active'
                        ? 'دسته‌بندی مطابق فیلتر پیدا نشد.'
                        : 'دسته‌بندی حذف‌شده‌ای وجود ندارد.',
                    false,
                    null,
                    false
                );

                renderPagination(kind);
                return;
            }

            const body = $(ids(kind)[0]);
            body.replaceChildren();

            items.forEach(cat => {
                const row = body.insertRow();
                const name = row.insertCell();
                const icon = d.createElement('i');

                const [symbol, color] = visual(cat);

                icon.className =
                    'fa-solid ' + symbol + ' ' + color + ' me-2';

                icon.setAttribute('aria-hidden', 'true');

                name.append(icon, d.createTextNode(cat.name || '—'));

                td(row, cat.type === 'income' ? 'درآمد' : 'هزینه');

                if (kind === 'active') {
                    td(
                        row,
                        fmt().date(cat.created_at),
                        'text-nowrap'
                    );

                    const controls = row.insertCell();

                    action(
                        controls,
                        'ویرایش',
                        'edit',
                        cat.id,
                        'primary'
                    );

                    action(
                        controls,
                        'حذف',
                        'delete',
                        cat.id,
                        'danger'
                    );
                } else {
                    td(
                        row,
                        fmt().dateTime(cat.deleted_at),
                        'text-nowrap'
                    );

                    const allowed = cat.restore_available === true;

                    td(
                        row,
                        allowed
                            ? 'در مهلت بازیابی'
                            : 'مهلت بازیابی تمام شده',
                        allowed ? 'text-success' : 'text-warning'
                    );

                    action(
                        row.insertCell(),
                        'بازیابی',
                        'restore',
                        cat.id,
                        'success'
                    ).disabled = !allowed || s.busy;
                }
            });

            renderPagination(kind);
        }

        function bind() {
            $('categoriesTableBody').addEventListener('click', event => {
                const button = event.target.closest('button[data-action]');
                if (!button) return;

                const cat = s.active.find(
                    item => String(item.id) === button.dataset.id
                );

                if (!cat) return;

                if (button.dataset.action === 'edit') {
                    edit(cat);
                }

                if (button.dataset.action === 'delete') {
                    askDelete(cat);
                }
            });

            $('deletedCategoriesTableBody').addEventListener(
                'click',
                event => {
                    const button = event.target.closest(
                        'button[data-action="restore"]'
                    );

                    if (button && !button.disabled) {
                        restore(button.dataset.id, button);
                    }
                }
            );

            ['categoriesSearch', 'categoriesTypeFilter'].forEach(id => {
                $(id).disabled = false;

                $(id).addEventListener(
                    id === 'categoriesSearch' ? 'input' : 'change',
                    () => {
                        // با تغییر فیلتر، به صفحه اول برمی‌گردیم.
                        changePage('active', 1);
                        render('active');
                    }
                );
            });

            const badge = d.querySelector(
                '[data-component="category-filters"] .badge'
            );

            if (badge) badge.textContent = 'فعال';

            $('categoriesFiltersNote').textContent =
                'جست‌وجو و فیلتر فعلاً روی دسته‌بندی‌های صفحه دریافت‌شده انجام می‌شوند.';
        }

        return { bind, render, empty };
    }

    w.TarazinoCategoryList = Object.freeze({ create });
})(window, document);
