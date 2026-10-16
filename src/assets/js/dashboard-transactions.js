
/** TARAZINO | Recent transactions, transaction modal and quick category. */
(function (w, d) {
'use strict';
const $ = id => d.getElementById(id);
const api = () => w.TarazinoApi;
const money = value => w.PersianFormat.number(value);
function notice(id, message, type = 'danger') {
    const el = $(id);
    if (!el) return;
    el.textContent = message;
    el.className = message ? 'alert alert-' + type : 'alert d-none';
}
function dateValid(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
    const [y, m, day] = value.split('-').map(Number);
    const date = new Date(Date.UTC(y, m - 1, day));
    return date.getUTCFullYear() === y && date.getUTCMonth() + 1 === m && date.getUTCDate() === day;
}
function amountValue(value) {
    const raw = w.PersianFormat.normalizeInputNumber(value).trim();
    if (!/^\d+(?:\.\d{1,2})?$/.test(raw)) return null;
    const number = Number(raw);
    return Number.isFinite(number) && number > 0 && number <= 9999999999999 ? raw : null;
}
function errorField(suffix, message) {
    const input = $('dashboardTransaction' + suffix);
    const box = $('dashboardTransaction' + suffix + 'Error');
    if (input) input.classList.toggle('is-invalid', !!message);
    if (box) box.textContent = message || '';
}
function option(select, value, label) {
    const item = d.createElement('option');
    item.value = value;
    item.textContent = label;
    select.append(item);
}
function categoriesForType(state, selected = '') {
    const select = $('dashboardTransactionCategory');
    const type = $('dashboardTransactionType').value;
    select.replaceChildren();
    option(select, '', type ? 'دسته‌بندی را انتخاب کنید' : 'ابتدا نوع را انتخاب کنید');
    state.categories.filter(c => c.type === type).forEach(c => option(select, String(c.id), c.name));
    select.value = selected || '';
}
function resetForm(state) {
    $('dashboardTransactionForm').reset();
    state.editingId = null;
    $('dashboardTransactionModalTitle').textContent = 'افزودن تراکنش';
    ['Title', 'Type', 'Category', 'Amount', 'Date'].forEach(s => errorField(s, ''));
    notice('dashboardTransactionFormMessage', '');
    categoriesForType(state);
    if (w.TarazinoDatepicker) w.TarazinoDatepicker.refresh($('dashboardTransactionModal'));
}
function editTransaction(state, tx) {
    resetForm(state);
    state.editingId = String(tx.id);
    $('dashboardTransactionModalTitle').textContent = 'ویرایش تراکنش';
    $('dashboardTransactionTitle').value = tx.title || '';
    $('dashboardTransactionAmount').value = w.PersianFormat.digits(tx.amount || '');
    $('dashboardTransactionDate').value = tx.transaction_date || '';
    $('dashboardTransactionDescription').value = tx.description || '';
    const category = state.categories.find(c => String(c.id) === String(tx.category_id));
    $('dashboardTransactionType').value = category ? category.type : '';
    categoriesForType(state, String(tx.category_id));
    if (w.TarazinoDatepicker) w.TarazinoDatepicker.refresh($('dashboardTransactionModal'));
    w.bootstrap.Modal.getOrCreateInstance($('dashboardTransactionModal')).show();
}
function render(state) {
    const body = $('dashboardTransactionsBody');
    body.replaceChildren();
    const transactions = [...state.transactions].sort((a, b) =>
        String(b.transaction_date || '').localeCompare(String(a.transaction_date || '')) || Number(b.id) - Number(a.id));
    if (!transactions.length) {
        const row = body.insertRow(), cell = row.insertCell();
        cell.colSpan = 5;
        cell.className = 'text-center text-body-secondary py-5';
        cell.textContent = 'هنوز تراکنشی ثبت نشده است.';
    }
    const categoryById = new Map(state.categories.map(c => [String(c.id), c]));
    transactions.slice(0, 5).forEach(tx => {
        const row = body.insertRow();
        const category = categoryById.get(String(tx.category_id));
        [tx.title || '—', category ? category.name : '—',
            (category && category.type === 'expense' ? '− ' : '+ ') + money(Number(tx.amount)),
            w.PersianFormat.date(tx.transaction_date)].forEach((value, i) => {
            const td = row.insertCell();
            td.textContent = value;
            if (i === 2) td.className = category && category.type === 'expense' ? 'text-danger' : 'text-success';
        });
        const actions = row.insertCell();
        actions.className = 'text-nowrap';
        [['edit', 'ویرایش', 'outline-primary'], ['delete', 'حذف', 'outline-danger']].forEach(([action, label, kind]) => {
            const button = d.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-' + kind + ' me-1';
            button.textContent = label;
            button.dataset.action = action;
            button.dataset.id = String(tx.id);
            actions.append(button);
        });
    });
    $('addDashboardTransactionButton').disabled = state.categories.length === 0;
}
async function save(state, reload, event) {
    event.preventDefault();
    if (state.saving) return;
    const title = $('dashboardTransactionTitle').value.trim();
    const type = $('dashboardTransactionType').value;
    const categoryId = $('dashboardTransactionCategory').value;
    const amount = amountValue($('dashboardTransactionAmount').value);
    const date = $('dashboardTransactionDate').value;
    const category = state.categories.find(c => String(c.id) === categoryId);
    const problems = {
        Title: title.length && title.length <= 150 ? '' : 'عنوان باید بین ۱ تا ۱۵۰ نویسه باشد.',
        Type: ['income', 'expense'].includes(type) ? '' : 'نوع تراکنش را انتخاب کنید.',
        Category: category && category.type === type ? '' : 'دسته‌بندی مناسب را انتخاب کنید.',
        Amount: amount ? '' : 'مبلغ مثبت معتبر با حداکثر دو رقم اعشار وارد کنید.',
        Date: dateValid(date) ? '' : 'تاریخ معتبر انتخاب کنید.'
    };
    Object.keys(problems).forEach(k => errorField(k, problems[k]));
    if (Object.values(problems).some(Boolean)) return;
    const data = new FormData();
    [['title', title], ['category_id', categoryId], ['amount', amount],
        ['transaction_date', date], ['description', $('dashboardTransactionDescription').value]]
        .forEach(([key, value]) => data.append(key, value));
    const button = $('saveDashboardTransactionButton');
    button.disabled = true;
    state.saving = true;
    try {
        const url = state.transactionsUrl + (state.editingId ? '/' + encodeURIComponent(state.editingId) + '/update' : '');
        const result = await api().post(url, data);
        w.bootstrap.Modal.getOrCreateInstance($('dashboardTransactionModal')).hide();
        notice('dashboardMessage', result.message || 'تراکنش ذخیره شد.', 'success');
        await reload();
    } catch (error) {
        notice('dashboardTransactionFormMessage', error.message);
    } finally {
        state.saving = false;
        button.disabled = false;
    }
}
async function remove(state, reload) {
    if (!state.deletingId || state.deleting) return;
    const button = $('confirmDashboardDeleteButton');
    button.disabled = true;
    state.deleting = true;
    try {
        const result = await api().post(state.transactionsUrl + '/' + encodeURIComponent(state.deletingId) + '/delete');
        w.bootstrap.Modal.getOrCreateInstance($('dashboardDeleteTransactionModal')).hide();
        notice('dashboardMessage', result.message || 'تراکنش حذف شد.', 'success');
        await reload();
    } catch (error) {
        notice('dashboardDeleteTransactionMessage', error.message);
    } finally {
        state.deletingId = null;
        state.deleting = false;
        button.disabled = false;
    }
}
function quickCategory(state, reload) {
    const modal = $('dashboardAddCategoryModal');
    if (!modal) return;
    const footer = modal.querySelector('.modal-footer');
    const oldLink = footer.querySelector('a.btn-primary');
    if (oldLink) oldLink.remove();
    const button = d.createElement('button');
    button.type = 'button'; button.className = 'btn btn-primary';
    button.textContent = 'ثبت دسته‌بندی'; footer.append(button);
    const message = d.createElement('div');
    message.className = 'alert d-none mt-3'; modal.querySelector('.modal-body').append(message);
    button.addEventListener('click', async () => {
        const name = $('dashboardCategoryName').value.trim();
        const type = $('dashboardCategoryType').value;
        if (!name || name.length > 100 || !['income', 'expense'].includes(type)) {
            message.textContent = 'نام و نوع دسته‌بندی معتبر وارد کنید.';
            message.className = 'alert alert-danger mt-3';
            return;
        }
        button.disabled = true;
        const data = new FormData(); data.append('name', name); data.append('type', type);
        try {
            const result = await api().post(state.categoriesUrl + '/store', data);
            w.bootstrap.Modal.getOrCreateInstance(modal).hide();
            notice('dashboardMessage', result.message || 'دسته‌بندی ثبت شد.', 'success');
            await reload();
        } catch (error) {
            message.textContent = error.message;
            message.className = 'alert alert-danger mt-3';
        } finally { button.disabled = false; }
    });
    modal.addEventListener('hidden.bs.modal', () => {
        $('dashboardCategoryName').value = '';
        $('dashboardCategoryType').value = '';
        message.className = 'alert d-none mt-3'; message.textContent = '';
    });
}
function initialize(state, reload) {
    $('dashboardTransactionForm').addEventListener('submit', event => save(state, reload, event));
    $('dashboardTransactionType').addEventListener('change', () => categoriesForType(state));
    ['Title', 'Type', 'Category', 'Amount', 'Date'].forEach(suffix => {
        const field = $('dashboardTransaction' + suffix);
        field.addEventListener('input', () => {
            if (suffix === 'Amount') {
                const caret = field.selectionStart;
                field.value = w.PersianFormat.digits(field.value);
                if (caret !== null) field.setSelectionRange(caret, caret);
            }
            errorField(suffix, '');
        });
        field.addEventListener('change', () => errorField(suffix, ''));
    });
    $('dashboardTransactionsBody').addEventListener('click', event => {
        const button = event.target.closest('button[data-action]');
        if (!button) return;
        const tx = state.transactions.find(t => String(t.id) === button.dataset.id);
        if (!tx) return;
        if (button.dataset.action === 'edit') editTransaction(state, tx);
        if (button.dataset.action === 'delete') {
            state.deletingId = String(tx.id);
            $('dashboardDeleteTransactionName').textContent = tx.title || '';
            notice('dashboardDeleteTransactionMessage', '');
            w.bootstrap.Modal.getOrCreateInstance($('dashboardDeleteTransactionModal')).show();
        }
    });
    $('confirmDashboardDeleteButton').addEventListener('click', () => remove(state, reload));
    $('dashboardTransactionModal').addEventListener('hidden.bs.modal', () => resetForm(state));
    quickCategory(state, reload);
}
w.TarazinoTransactions = Object.freeze({ initialize, render });
})(window, document);
