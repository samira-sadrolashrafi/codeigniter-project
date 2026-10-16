
/** TARAZINO | Category tables, filters and pagination; no API calls. */
(function (w, d) {
    'use strict';
    function create(ctx) {
        const { s, $, fmt, edit, askDelete, restore } = ctx;
        const PAGE_SIZE = 10;
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
        function empty(kind, text, isError = false, retry = null) {
            const [bodyId, paginationId] = ids(kind);
            const body = $(bodyId);
            body.replaceChildren();
            const cell = body.insertRow().insertCell();
            cell.colSpan = kind === 'active' ? 4 : 5;
            cell.className = 'text-center py-5 ' + (isError ? 'text-danger' : 'text-body-secondary');
            cell.textContent = text;
            $(paginationId).classList.add('d-none');
            if (retry) {
                const button = d.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-outline-primary ms-2';
                button.textContent = 'تلاش دوباره';
                button.addEventListener('click', retry);
                cell.appendChild(button);
            }
        }
        function filtered() {
            const query = $('categoriesSearch').value.trim().toLocaleLowerCase('fa-IR');
            const type = $('categoriesTypeFilter').value;
            return s.active.filter(cat =>
                (!query || String(cat.name || '').toLocaleLowerCase('fa-IR').includes(query)) &&
                (type === 'all' || cat.type === type)
            );
        }
        function paginate(kind, items, paint) {
            const [, prefix] = ids(kind);
            const count = Math.max(1, Math.ceil(items.length / PAGE_SIZE));
            s.page[kind] = Math.min(Math.max(1, s.page[kind]), count);
            const start = (s.page[kind] - 1) * PAGE_SIZE;
            paint(items.slice(start, start + PAGE_SIZE));
            $(prefix).classList.toggle('d-none', items.length <= PAGE_SIZE);
            $(prefix + 'Summary').textContent = items.length
                ? `نمایش ${fmt().integer(start + 1)} تا ${fmt().integer(Math.min(items.length, start + PAGE_SIZE))} از ${fmt().integer(items.length)}`
                : 'موردی وجود ندارد';
            const list = $(prefix + 'List');
            list.replaceChildren();
            if (items.length <= PAGE_SIZE) return;
            function pageButton(text, page, disabled, selected = false) {
                const li = d.createElement('li'), button = d.createElement('button');
                li.className = 'page-item' + (disabled ? ' disabled' : '') + (selected ? ' active' : '');
                button.type = 'button';
                button.className = 'page-link';
                button.textContent = text;
                button.disabled = disabled;
                if (selected) button.setAttribute('aria-current', 'page');
                button.addEventListener('click', () => { s.page[kind] = page; render(kind); });
                li.appendChild(button);
                list.appendChild(li);
            }
            pageButton('قبلی', s.page[kind] - 1, s.page[kind] === 1);
            const first = Math.max(1, Math.min(s.page[kind] - 2, count - 4));
            for (let page = first; page <= Math.min(count, first + 4); page++) {
                pageButton(fmt().integer(page), page, false, page === s.page[kind]);
            }
            pageButton('بعدی', s.page[kind] + 1, s.page[kind] === count);
        }
        // Visual defaults are presentation-only; no icon/color columns in DB.
        function visual(category) {
            const name = String(category.name || '');
            const presets = category.type === 'income'
                ? [
                    [/حقوق|دستمزد/, 'fa-money-bill-wave', 'text-success'],
                    [/فروش/, 'fa-cart-shopping', 'text-primary'],
                    [/هدیه/, 'fa-gift', 'text-info']
                ] : [
                    [/خوراک|غذا/, 'fa-utensils', 'text-warning'],
                    [/حمل|رفت.*آمد/, 'fa-car', 'text-info'],
                    [/مسکن|اجاره/, 'fa-house', 'text-primary'],
                    [/درمان|سلامت/, 'fa-heart-pulse', 'text-danger'],
                    [/آموزش/, 'fa-graduation-cap', 'text-info']
                ];
            const match = presets.find(([pattern]) => pattern.test(name));
            return match ? match.slice(1) : category.type === 'income'
                ? ['fa-arrow-trend-up', 'text-success']
                : ['fa-arrow-trend-down', 'text-danger'];
        }
        function render(kind) {
            const items = kind === 'active' ? filtered() : s.deleted;
            paginate(kind, items, subset => {
                if (!subset.length) {
                    empty(kind, kind === 'active' ? 'دسته‌بندی مطابق فیلتر پیدا نشد.' : 'دسته‌بندی حذف‌شده‌ای وجود ندارد.');
                    return;
                }
                const body = $(ids(kind)[0]);
                body.replaceChildren();
                subset.forEach(cat => {
                    const row = body.insertRow();
                    const name = row.insertCell();
                    const icon = d.createElement('i');
                    const [symbol, color] = visual(cat);
                    icon.className = 'fa-solid ' + symbol + ' ' + color + ' me-2';
                    icon.setAttribute('aria-hidden', 'true');
                    name.append(icon, d.createTextNode(cat.name || '—'));
                    td(row, cat.type === 'income' ? 'درآمد' : 'هزینه');
                    if (kind === 'active') {
                        td(row, fmt().date(cat.created_at), 'text-nowrap');
                        const controls = row.insertCell();
                        action(controls, 'ویرایش', 'edit', cat.id, 'primary');
                        action(controls, 'حذف', 'delete', cat.id, 'danger');
                    } else {
                        td(row, fmt().dateTime(cat.deleted_at), 'text-nowrap');
                        const allowed = cat.restore_available === true;
                        td(row, allowed ? 'در مهلت بازیابی' : 'مهلت بازیابی تمام شده',
                            allowed ? 'text-success' : 'text-warning');
                        action(row.insertCell(), 'بازیابی', 'restore', cat.id, 'success')
                            .disabled = !allowed || s.busy;
                    }
                });
            });
        }
        function bind() {
            $('categoriesTableBody').addEventListener('click', event => {
                const button = event.target.closest('button[data-action]');
                if (!button) return;
                const cat = s.active.find(x => String(x.id) === button.dataset.id);
                if (!cat) return;
                if (button.dataset.action === 'edit') edit(cat);
                if (button.dataset.action === 'delete') askDelete(cat);
            });
            $('deletedCategoriesTableBody').addEventListener('click', event => {
                const button = event.target.closest('button[data-action="restore"]');
                if (button && !button.disabled) restore(button.dataset.id, button);
            });
            ['categoriesSearch', 'categoriesTypeFilter'].forEach(id => {
                $(id).disabled = false;
                $(id).addEventListener(id === 'categoriesSearch' ? 'input' : 'change', () => {
                    s.page.active = 1;
                    render('active');
                });
            });
            const badge = d.querySelector('[data-component="category-filters"] .badge');
            if (badge) badge.textContent = 'فعال';
            $('categoriesFiltersNote').textContent = 'جست‌وجو و فیلتر روی دسته‌بندی‌های فعال انجام می‌شوند.';
        }
        return { bind, render, empty };
    }
    w.TarazinoCategoryList = Object.freeze({ create });
})(window, document);
