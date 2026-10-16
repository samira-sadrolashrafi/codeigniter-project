
/** TARAZINO | Filter, render and paginate active/deleted transaction tables. */
(function (w, d) {
'use strict';
function create(ctx) {
    const { s, $, fmt, categoryOf, restore, edit, askDelete } = ctx;
    const size = 10;
    function empty(kind, text, error = false) {
        const id = kind === 'active' ? 'transactionsTableBody' : 'deletedTransactionsTableBody';
        const body = $(id);
        body.replaceChildren();
        const cell = body.insertRow().insertCell();
        cell.colSpan = kind === 'active' ? 7 : 6;
        cell.className = 'text-center py-5 ' + (error ? 'text-danger' : 'text-body-secondary');
        cell.textContent = text;
        const prefix = kind === 'active' ? 'transactions' : 'deletedTransactions';
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
        btn.textContent = label; btn.dataset.action = action; btn.dataset.id = String(id);
        parent.append(btn);
        return btn;
    }
    function paginate(items, kind, draw) {
        const total = Math.max(1, Math.ceil(items.length / size));
        s.page[kind] = Math.min(Math.max(s.page[kind], 1), total);
        const from = (s.page[kind] - 1) * size;
        draw(items.slice(from, from + size));
        const prefix = kind === 'active' ? 'transactions' : 'deletedTransactions';
        $(prefix + 'Pagination').classList.toggle('d-none', items.length <= size);
        $(prefix + 'PaginationSummary').textContent = items.length
            ? `نمایش ${fmt().integer(from + 1)} تا ${fmt().integer(Math.min(from + size, items.length))} از ${fmt().integer(items.length)}`
            : 'موردی وجود ندارد';
        const list = $(prefix + 'PaginationList');
        list.replaceChildren();
        if (items.length <= size) return;
        function add(label, number, disabled, selected = false) {
            const li = d.createElement('li'), btn = d.createElement('button');
            li.className = 'page-item' + (disabled ? ' disabled' : '') + (selected ? ' active' : '');
            btn.type = 'button'; btn.className = 'page-link'; btn.textContent = label;
            btn.disabled = disabled;
            if (selected) btn.setAttribute('aria-current', 'page');
            btn.addEventListener('click', () => { s.page[kind] = number; render(kind); });
            li.append(btn); list.append(li);
        }
        add('قبلی', s.page[kind] - 1, s.page[kind] === 1);
        const start = Math.max(1, Math.min(s.page[kind] - 2, total - 4));
        for (let page = start; page <= Math.min(total, start + 4); page++) {
            add(fmt().integer(page), page, false, page === s.page[kind]);
        }
        add('بعدی', s.page[kind] + 1, s.page[kind] === total);
    }
    function filtered() {
        const search = $('transactionSearch').value.trim().toLocaleLowerCase('fa-IR');
        const type = $('transactionTypeFilter').value;
        const categoryId = $('transactionCategoryFilter').value;
        const from = $('transactionDateFrom').value, to = $('transactionDateTo').value;
        return s.active.filter(tx => {
            const cat = categoryOf(tx);
            if (search && ![tx.title, tx.description].some(v => String(v || '').toLocaleLowerCase('fa-IR').includes(search))) return false;
            if (type !== 'all' && (!cat || cat.type !== type)) return false;
            if (categoryId !== 'all' && String(tx.category_id) !== categoryId) return false;
            if (from && tx.transaction_date < from) return false;
            if (to && tx.transaction_date > to) return false;
            return true;
        }).sort((a, b) => String(b.transaction_date || '').localeCompare(String(a.transaction_date || '')) || Number(b.id) - Number(a.id));
    }
    function active() {
        paginate(filtered(), 'active', items => {
            if (!items.length) { empty('active', 'تراکنشی با این شرایط پیدا نشد.'); return; }
            const body = $('transactionsTableBody'); body.replaceChildren();
            items.forEach(tx => {
                const cat = categoryOf(tx), type = cat ? cat.type : '';
                const row = body.insertRow();
                cell(row, tx.title || '—');
                cell(row, type === 'income' ? 'درآمد' : type === 'expense' ? 'هزینه' : 'نامشخص');
                cell(row, fmt().number(tx.amount), type === 'expense' ? 'text-danger' : 'text-success');
                cell(row, cat ? cat.name : '—');
                cell(row, fmt().date(tx.transaction_date), 'text-nowrap');
                cell(row, tx.description || '—');
                const actions = row.insertCell(); actions.className = 'text-nowrap';
                button(actions, 'ویرایش', 'edit', tx.id, 'primary');
                button(actions, 'حذف', 'delete', tx.id, 'danger');
            });
        });
    }
    function deleted() {
        paginate(s.deleted, 'deleted', items => {
            if (!items.length) { empty('deleted', 'تراکنش حذف‌شده‌ای وجود ندارد.'); return; }
            const body = $('deletedTransactionsTableBody'); body.replaceChildren();
            items.forEach(tx => {
                const row = body.insertRow();
                cell(row, tx.title || '—'); cell(row, fmt().number(tx.amount));
                cell(row, tx.category_name || '—'); cell(row, fmt().date(tx.transaction_date));
                cell(row, fmt().dateTime(tx.deleted_at));
                const actions = row.insertCell();
                const allowed = tx.restore_available === true && tx.category_deleted_at == null && !!tx.category_name;
                const reason = !tx.restore_available ? 'مهلت بازیابی تمام شده' :
                    tx.category_deleted_at != null || !tx.category_name ? 'ابتدا دسته‌بندی مربوط را بازیابی کنید' : 'قابل بازیابی';
                cellStatus(actions, reason, allowed);
                button(actions, 'بازیابی', 'restore', tx.id, 'success').disabled = !allowed || s.busy;
            });
        });
    }
    function cellStatus(parent, text, okay) {
        const label = d.createElement('span');
        label.className = 'small d-block mb-2 ' + (okay ? 'text-success' : 'text-warning');
        label.textContent = text; parent.append(label);
    }
    function render(kind) { if (kind === 'active') active(); else deleted(); }
    function categories() {
        const select = $('transactionCategoryFilter'), previous = select.value;
        const type = $('transactionTypeFilter').value;
        select.replaceChildren();
        [{ id: 'all', name: 'همه دسته‌بندی‌ها' }, ...s.categories.filter(c => type === 'all' || c.type === type)]
            .forEach(c => { const option = d.createElement('option'); option.value = String(c.id);
                option.textContent = c.name; select.append(option); });
        if ([...select.options].some(o => o.value === previous)) select.value = previous;
    }
    function bind() {
        $('transactionsTableBody').addEventListener('click', event => {
            const btn = event.target.closest('button[data-action]');
            if (!btn) return;
            const tx = s.active.find(t => String(t.id) === btn.dataset.id);
            if (!tx) return;
            if (btn.dataset.action === 'edit') edit(tx);
            if (btn.dataset.action === 'delete') askDelete(tx);
        });
        $('deletedTransactionsTableBody').addEventListener('click', event => {
            const btn = event.target.closest('button[data-action="restore"]');
            if (btn && !btn.disabled) restore(btn.dataset.id, btn);
        });
        ['transactionSearch', 'transactionDateFrom', 'transactionDateTo'].forEach(id => {
            ['input', 'change'].forEach(type => $(id).addEventListener(type, () => { s.page.active = 1; active(); }));
        });
        $('transactionTypeFilter').addEventListener('change', () => { categories(); s.page.active = 1; active(); });
        $('transactionCategoryFilter').addEventListener('change', () => { s.page.active = 1; active(); });
    }
    return { bind, render, empty, categories };
}
w.TarazinoTransactionList = Object.freeze({ create });
})(window, document);
