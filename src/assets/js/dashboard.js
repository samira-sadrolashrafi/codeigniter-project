
/** TARAZINO | Small dashboard coordinator and manual balance actions. */
(function (w, d) {
'use strict';
const $ = id => d.getElementById(id);
function message(id, text, type = 'danger') {
    const el = $(id);
    if (!el) return;
    el.textContent = text;
    el.className = text ? 'alert alert-' + type : 'alert d-none';
}
function parseAdjustment(value) {
    const raw = w.PersianFormat.normalizeInputNumber(value).trim();
    if (!/^-?\d+(?:\.\d{1,2})?$/.test(raw)) return null;
    const integer = raw.replace(/^-/, '').split('.')[0].replace(/^0+/, '') || '0';
    return integer.length <= 13 ? raw : null;
}
async function loadBalance(state) {
    try {
        const response = await w.TarazinoApi.get(state.balanceUrl);
        const amount = Number(response.balance);
        if (!Number.isFinite(amount)) throw new Error('مقدار موجودی نامعتبر است.');
        $('balanceAmount').textContent = w.PersianFormat.number(Math.max(0, amount));
        $('debtAmount').textContent = w.PersianFormat.number(Math.max(0, -amount));
    } catch (error) {
        $('balanceAmount').textContent = $('debtAmount').textContent = '—';
        message('dashboardMessage', error.message);
    }
}
async function reload(state, refreshReport = true) {
    if (state.loading) return;
    state.loading = true;
    try {
        const [tx, cat] = await Promise.all([
            w.TarazinoApi.get(state.transactionsUrl),
            w.TarazinoApi.get(state.categoriesUrl)
        ]);
        if (!Array.isArray(tx.transactions) || !Array.isArray(cat.categories)) {
            throw new Error('ساختار اطلاعات دریافتی معتبر نیست.');
        }
        state.transactions = tx.transactions;
        state.categories = cat.categories;
        w.TarazinoTransactions.render(state);
    } catch (error) {
        state.transactions = [];
        state.categories = [];
        w.TarazinoTransactions.render(state);
        message('dashboardMessage', error.message);
    } finally {
        state.loading = false;
    }
    // Reports are independent of the recent-transactions/categories requests.
    await Promise.all([
        loadBalance(state),
        refreshReport ? w.TarazinoReports.render() : Promise.resolve()
    ]);
}
function wireBalance(state) {
    const form = $('balanceForm'), input = $('balanceInput');
    if (!form || !input) return;
    input.addEventListener('input', () => {
        const caret = input.selectionStart;
        input.value = w.PersianFormat.digits(input.value);
        if (caret !== null) input.setSelectionRange(caret, caret);
        input.classList.remove('is-invalid');
        $('balanceInputError').classList.remove('d-block');
        message('balanceModalMessage', '');
    });
    $('balanceModal').addEventListener('hidden.bs.modal', () => message('balanceModalMessage', ''));
    $('resetBalanceModal').addEventListener('hidden.bs.modal', () => message('resetBalanceMessage', ''));
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const amount = parseAdjustment(input.value);
        if (amount === null) {
            input.classList.add('is-invalid');
            $('balanceInputError').textContent = 'مبلغ معتبر (حداکثر دو رقم اعشار) وارد کنید.';
            $('balanceInputError').classList.add('d-block');
            return;
        }
        const button = $('saveBalanceButton');
        button.disabled = true;
        const data = new FormData(); data.append('balance', amount);
        try {
            const response = await w.TarazinoApi.post(state.balanceUrl, data);
            w.bootstrap.Modal.getOrCreateInstance($('balanceModal')).hide();
            input.value = '';
            message('dashboardMessage', response.message || 'موجودی تغییر کرد.', 'success');
            await Promise.all([loadBalance(state), w.TarazinoReports.render()]);
        } catch (error) {
            message('balanceModalMessage', error.message);
        } finally { button.disabled = false; }
    });
    $('confirmResetBalanceButton').addEventListener('click', async () => {
        const button = $('confirmResetBalanceButton');
        button.disabled = true;
        try {
            const response = await w.TarazinoApi.post(state.balanceUrl + '/reset');
            w.bootstrap.Modal.getOrCreateInstance($('resetBalanceModal')).hide();
            message('dashboardMessage', response.message || 'موجودی صفر شد.', 'success');
            await Promise.all([loadBalance(state), w.TarazinoReports.render()]);
        } catch (error) {
            message('resetBalanceMessage', error.message);
        } finally { button.disabled = false; }
    });
}
function initialize() {
    const root = d.querySelector('.dashboard-shell[data-balance-url]');
    if (!root) return;
    if (!w.PersianFormat || !w.TarazinoApi || !w.TarazinoReports || !w.TarazinoTransactions) {
        message('dashboardMessage', 'یکی از فایل‌های مشترک داشبورد بارگذاری نشده است.');
        return;
    }
    const state = {
        balanceUrl: root.dataset.balanceUrl,
        categoriesUrl: root.dataset.categoriesUrl,
        transactionsUrl: root.dataset.transactionsUrl,
        transactions: [], categories: [], loading: false, saving: false,
        deleting: false, editingId: null, deletingId: null
    };
    w.TarazinoReports.initialize(state);
    w.TarazinoTransactions.initialize(state, () => reload(state));
    wireBalance(state);
    // The report module loads its initial data on its own. Do not duplicate that API request.
    reload(state, false);
}
if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
})(window, document);
