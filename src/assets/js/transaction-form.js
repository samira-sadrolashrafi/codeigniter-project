
/** TARAZINO | Add/edit transaction modal. Loaded before transactions.js. */
(function (w, d) {
'use strict';
function create(ctx) {
    const { s, $, fmt, api, notice, categoryOf, reloadActive } = ctx;
    let editingId = null;
    const field = name => $('transaction' + name);
    const modal = () => w.bootstrap.Modal.getOrCreateInstance($('transactionModal'));

    function error(name, text = '') {
        field(name).classList.toggle('is-invalid', !!text);
        $('transaction' + name + 'Error').textContent = text;
    }
    function categoryOptions(selected = '') {
        const select = field('Category'), type = field('Type').value;
        select.replaceChildren();
        const choices = [{ id: '', name: type ? 'دسته‌بندی را انتخاب کنید' : 'ابتدا نوع تراکنش را انتخاب کنید' },
            ...s.categories.filter(c => c.type === type)];
        choices.forEach(c => {
            const option = d.createElement('option');
            option.value = String(c.id); option.textContent = c.name;
            select.append(option);
        });
        if ([...select.options].some(o => o.value === String(selected))) select.value = String(selected);
        const warning = type && choices.length === 1 ? 'برای این نوع تراکنش ابتدا دسته‌بندی بسازید.' : '';
        notice('transactionCategoriesWarning', warning, 'warning');
    }
    function refreshDate() {
        if (w.TarazinoDatepicker) w.TarazinoDatepicker.refresh($('transactionModal'));
    }
    function reset() {
        $('transactionForm').reset();
        editingId = null;
        $('transactionModalTitle').textContent = 'افزودن تراکنش';
        notice('transactionFormMessage', '');
        ['Title', 'Type', 'Amount', 'Category', 'Date'].forEach(name => error(name));
        categoryOptions(); refreshDate();
    }
    function open(tx = null) {
        reset();
        if (tx) {
            editingId = String(tx.id);
            $('transactionModalTitle').textContent = 'ویرایش تراکنش';
            field('Title').value = tx.title || '';
            field('Amount').value = fmt().digits(tx.amount == null ? '' : tx.amount);
            field('Date').value = tx.transaction_date || '';
            field('Description').value = tx.description || '';
            const category = categoryOf(tx);
            field('Type').value = category ? category.type : '';
            categoryOptions(tx.category_id);
        } else {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            field('Date').value = [now.getFullYear(), pad(now.getMonth() + 1), pad(now.getDate())].join('-');
            if (!s.categories.length) notice('transactionCategoriesWarning', 'ابتدا یک دسته‌بندی فعال ایجاد کنید.', 'warning');
        }
        refreshDate(); modal().show();
    }
    function validDate(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
        const [y, m, day] = value.split('-').map(Number);
        const check = new Date(Date.UTC(y, m - 1, day));
        return check.getUTCFullYear() === y && check.getUTCMonth() + 1 === m && check.getUTCDate() === day;
    }
    function payload() {
        const title = field('Title').value.trim();
        const type = field('Type').value, categoryId = field('Category').value;
        const date = field('Date').value;
        const raw = fmt().normalizeInputNumber(field('Amount').value).trim();
        const match = /^(\d+)(?:\.(\d{1,2}))?$/.exec(raw);
        const integer = match ? (match[1].replace(/^0+/, '') || '0') : '';
        const decimals = match ? (match[2] || '') : '';
        const amount = match && integer.length <= 13 && /[1-9]/.test(integer + decimals)
            ? integer + (decimals ? '.' + decimals : '') : null;
        const category = s.categories.find(c => String(c.id) === categoryId);
        const issues = {
            Title: [...title].length >= 1 && [...title].length <= 150 ? '' : 'عنوان باید بین ۱ تا ۱۵۰ نویسه باشد.',
            Type: ['income', 'expense'].includes(type) ? '' : 'نوع تراکنش را انتخاب کنید.',
            Category: category && category.type === type ? '' : 'دسته‌بندی مناسب را انتخاب کنید.',
            Amount: amount ? '' : 'مبلغ مثبت با حداکثر ۱۳ رقم و دو رقم اعشار وارد کنید.',
            Date: validDate(date) ? '' : 'تاریخ معتبر انتخاب کنید.'
        };
        Object.entries(issues).forEach(([name, text]) => error(name, text));
        if (Object.values(issues).some(Boolean)) return null;
        const form = new FormData();
        Object.entries({ title, category_id: categoryId, amount, transaction_date: date,
            description: field('Description').value }).forEach(([key, value]) => form.append(key, value));
        return form;
    }
    async function save(event) {
        event.preventDefault();
        if (s.busy) return;
        const data = payload();
        if (!data) return;
        const button = $('saveTransactionButton');
        s.busy = true; button.disabled = true;
        try {
            const url = s.url + (editingId ? '/' + encodeURIComponent(editingId) + '/update' : '');
            const result = await api().post(url, data);
            modal().hide();
            notice('transactionsMessage', result.message || 'تراکنش ذخیره شد.', 'success');
            try { await reloadActive(); }
            catch (e) { notice('transactionsMessage', 'ذخیره شد؛ اما فهرست به‌روز نشد: ' + e.message, 'warning'); }
        } catch (e) { notice('transactionFormMessage', e.message); }
        finally { s.busy = false; button.disabled = false; }
    }
    function bind() {
        $('transactionForm').addEventListener('submit', save);
        field('Type').addEventListener('change', () => { error('Type'); categoryOptions(); });
        ['Title', 'Type', 'Amount', 'Category', 'Date'].forEach(name => {
            const input = field(name);
            input.addEventListener('input', () => {
                if (name === 'Amount') {
                    const caret = input.selectionStart;
                    input.value = fmt().digits(input.value);
                    if (caret !== null) input.setSelectionRange(caret, caret);
                }
                error(name);
            });
            input.addEventListener('change', () => error(name));
        });
        $('transactionModal').addEventListener('hidden.bs.modal', reset);
    }
    return { open, bind };
}
w.TarazinoTransactionForm = Object.freeze({ create });
})(window, document);
