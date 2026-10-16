
/** TARAZINO | Transactions page coordinator. Load this AFTER form and list. */
(function (w, d) {
'use strict';
const $ = id => d.getElementById(id);
const fmt = () => w.PersianFormat;
const api = () => w.TarazinoApi;
const s = { url: '', categoryUrl: '', active: [], deleted: [], categories: [],
    categoriesLoaded: false, deletedLoaded: false, loadingDeleted: false,
    page: { active: 1, deleted: 1 }, busy: false, deletingId: null };
let form, list;
function notice(id, text, type = 'danger') {
    const element = $(id);
    if (!element) return;
    element.textContent = text || '';
    element.className = text ? 'alert alert-' + type : 'alert d-none';
}
function categoryOf(tx) {
    return s.categories.find(c => String(c.id) === String(tx.category_id));
}
async function reloadActive() {
    const [transactions, categories] = await Promise.all([api().get(s.url), api().get(s.categoryUrl)]);
    if (!Array.isArray(transactions.transactions) || !Array.isArray(categories.categories)) {
        throw new Error('ساختار پاسخ تراکنش‌ها یا دسته‌بندی‌ها معتبر نیست.');
    }
    s.active = transactions.transactions;
    s.categories = categories.categories;
    s.categoriesLoaded = true;
    $('addTransactionButton').disabled = false;
    list.categories(); list.render('active');
}
async function loadActive() {
    list.empty('active', 'در حال دریافت تراکنش‌ها...');
    try { await reloadActive(); }
    catch (error) {
        s.categoriesLoaded = false;
        $('addTransactionButton').disabled = true;
        list.empty('active', 'دریافت تراکنش‌ها انجام نشد.', true);
        notice('transactionsMessage', error.message + ' برای تلاش دوباره صفحه را تازه‌سازی کنید.');
    }
}
async function loadDeleted() {
    if (s.loadingDeleted) return;
    s.loadingDeleted = true;
    list.empty('deleted', 'در حال دریافت تراکنش‌های حذف‌شده...');
    try {
        const response = await api().get(s.url + '/deleted');
        if (!Array.isArray(response.transactions)) throw new Error('پاسخ فهرست حذف‌شده‌ها معتبر نیست.');
        s.deleted = response.transactions;
        s.deletedLoaded = true;
        list.render('deleted');
    } catch (error) {
        s.deletedLoaded = false;
        list.empty('deleted', error.message, true);
        notice('transactionsMessage', error.message);
        throw error;
    } finally { s.loadingDeleted = false; }
}
function askDelete(tx) {
    s.deletingId = String(tx.id);
    $('deleteTransactionTitle').textContent = tx.title || '';
    notice('deleteTransactionMessage', '');
    w.bootstrap.Modal.getOrCreateInstance($('deleteTransactionModal')).show();
}
async function remove() {
    if (s.busy || !s.deletingId) return;
    const button = $('confirmDeleteTransactionButton');
    s.busy = true; button.disabled = true;
    try {
        const result = await api().post(s.url + '/' + encodeURIComponent(s.deletingId) + '/delete');
        w.bootstrap.Modal.getOrCreateInstance($('deleteTransactionModal')).hide();
        notice('transactionsMessage', result.message || 'تراکنش حذف شد.', 'success');
        s.deletedLoaded = false;
        try { await reloadActive(); }
        catch (error) { notice('transactionsMessage', 'حذف شد؛ اما فهرست به‌روز نشد: ' + error.message, 'warning'); }
    } catch (error) { notice('deleteTransactionMessage', error.message); }
    finally { s.busy = false; s.deletingId = null; button.disabled = false; }
}
async function restore(id, button) {
    if (s.busy || !w.confirm('آیا تراکنش بازیابی شود؟ تغییرات مالی توسط سرور محاسبه می‌شوند.')) return;
    s.busy = true; button.disabled = true;
    try {
        const result = await api().post(s.url + '/' + encodeURIComponent(id) + '/restore');
        notice('transactionsMessage', result.message || 'تراکنش بازیابی شد.', 'success');
        s.deletedLoaded = false;
        const updates = await Promise.allSettled([reloadActive(), loadDeleted()]);
        if (updates.some(item => item.status === 'rejected')) {
            notice('transactionsMessage', 'بازیابی شد؛ اما دریافت دوباره اطلاعات ناقص ماند.', 'warning');
        }
    } catch (error) { notice('transactionsMessage', error.message); }
    finally { s.busy = false; if (s.deletedLoaded) list.render('deleted'); }
}
function switchTab(deleted) {
    $('activeTransactionsPanel').hidden = deleted;
    $('deletedTransactionsPanel').hidden = !deleted;
    $('activeTransactionsPanel').classList.toggle('d-none', deleted);
    $('deletedTransactionsPanel').classList.toggle('d-none', !deleted);
    $('showActiveTransactionsButton').className = 'btn ' + (deleted ? 'btn-outline-primary' : 'btn-primary');
    $('showDeletedTransactionsButton').className = 'btn ' + (deleted ? 'btn-primary' : 'btn-outline-primary');
    $('showActiveTransactionsButton').setAttribute('aria-pressed', String(!deleted));
    $('showDeletedTransactionsButton').setAttribute('aria-pressed', String(deleted));
    if (deleted && !s.deletedLoaded) loadDeleted().catch(() => {}); // Error is displayed in the panel.
}
function initialize() {
    const page = $('transactionsMain');
    if (!page) return;
    if (!api() || !fmt() || !w.bootstrap || !w.TarazinoTransactionForm || !w.TarazinoTransactionList) {
        notice('transactionsMessage', 'یکی از فایل‌های مشترک تراکنش‌ها بارگذاری نشده است.');
        return;
    }
    s.url = page.dataset.transactionsApiUrl.replace(/\/$/, '');
    s.categoryUrl = page.dataset.categoriesApiUrl.replace(/\/$/, '');
    const ctx = { s, $, fmt, api, notice, categoryOf, reloadActive, restore,
        edit: tx => form.open(tx), askDelete };
    form = w.TarazinoTransactionForm.create(ctx);
    list = w.TarazinoTransactionList.create(ctx);
    form.bind(); list.bind();
    $('addTransactionButton').addEventListener('click', () => {
        if (!s.categoriesLoaded) notice('transactionsMessage', 'دسته‌بندی‌ها هنوز دریافت نشده‌اند.');
        else form.open();
    });
    $('confirmDeleteTransactionButton').addEventListener('click', remove);
    $('showActiveTransactionsButton').addEventListener('click', () => switchTab(false));
    $('showDeletedTransactionsButton').addEventListener('click', () => switchTab(true));
    $('deleteTransactionModal').addEventListener('hidden.bs.modal', () => notice('deleteTransactionMessage', ''));
    switchTab(false); loadActive();
}
if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
})(window, document);
