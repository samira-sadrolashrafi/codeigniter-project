
/** TARAZINO | Categories coordinator: API, delete/restore, active/deleted tabs. */
(function (w, d) {
    'use strict';
    const $ = id => d.getElementById(id);
    const api = () => w.TarazinoApi;
    const fmt = () => w.PersianFormat;
    const s = { apiUrl: '', active: [], deleted: [], page: { active: 1, deleted: 1 },
        busy: false, deletingId: null, deletedLoaded: false, loadingDeleted: false };
    let form, list;

    function notice(id, text, type = 'danger') {
        const element = $(id);
        if (!element) return;
        element.textContent = text || '';
        element.className = text ? 'alert alert-' + type : 'alert d-none';
        if (id === 'deleteCategoryMessage') element.classList.add('mt-3', 'mb-0');
    }
    async function reloadActive() {
        const result = await api().get(s.apiUrl);
        if (!Array.isArray(result.categories)) throw new Error('فهرست دسته‌بندی‌ها معتبر نیست.');
        s.active = result.categories;
        list.render('active');
    }
    async function loadActive() {
        list.empty('active', 'در حال دریافت دسته‌بندی‌ها...');
        try { await reloadActive(); }
        catch (e) {
            list.empty('active', e.message || 'دریافت دسته‌بندی‌ها انجام نشد.', true, loadActive);
            notice('categoriesMessage', e.message);
        }
    }
    async function loadDeleted() {
        if (s.loadingDeleted) return;
        s.loadingDeleted = true;
        list.empty('deleted', 'در حال دریافت دسته‌بندی‌های حذف‌شده...');
        try {
            const result = await api().get(s.apiUrl + '/deleted');
            if (!Array.isArray(result.categories)) throw new Error('فهرست حذف‌شده‌ها معتبر نیست.');
            s.deleted = result.categories;
            s.deletedLoaded = true;
            list.render('deleted');
        } catch (e) {
            s.deletedLoaded = false;
            list.empty('deleted', e.message || 'دریافت انجام نشد.', true, () => { loadDeleted(); });
            notice('categoriesMessage', e.message);
            throw e;
        } finally { s.loadingDeleted = false; }
    }
    function askDelete(category) {
        s.deletingId = String(category.id);
        $('deleteCategoryName').textContent = category.name || '';
        notice('deleteCategoryMessage', '');
        w.bootstrap.Modal.getOrCreateInstance($('deleteCategoryModal')).show();
    }
    async function remove() {
        if (s.busy || s.deletingId === null) return;
        const button = $('confirmDeleteCategoryButton');
        s.busy = true; button.disabled = true;
        button.textContent = 'در حال حذف...';
        try {
            const result = await api().post(s.apiUrl + '/' + encodeURIComponent(s.deletingId) + '/delete');
            w.bootstrap.Modal.getOrCreateInstance($('deleteCategoryModal')).hide();
            notice('categoriesMessage', result.message || 'دسته‌بندی حذف شد.', 'success');
            s.deletedLoaded = false;
            try { await reloadActive(); }
            catch (e) { notice('categoriesMessage', 'حذف شد، اما فهرست به‌روز نشد: ' + e.message, 'warning'); }
        } catch (e) { notice('deleteCategoryMessage', e.message || 'حذف انجام نشد.'); }
        finally {
            s.busy = false; s.deletingId = null; button.disabled = false;
            button.textContent = 'حذف دسته‌بندی';
        }
    }
    async function restore(id, button) {
        if (s.busy || !w.confirm('دسته‌بندی بازیابی شود؟ تراکنش‌های حذف‌شده خودکار بازیابی نمی‌شوند.')) return;
        s.busy = true; button.disabled = true;
        button.textContent = 'در حال بازیابی...';
        try {
            const result = await api().post(s.apiUrl + '/' + encodeURIComponent(id) + '/restore');
            s.deletedLoaded = false;
            const updates = await Promise.allSettled([reloadActive(), loadDeleted()]);
            notice('categoriesMessage', updates.some(x => x.status === 'rejected')
                ? 'دسته‌بندی بازیابی شد؛ اما تازه‌سازی یکی از فهرست‌ها انجام نشد.'
                : (result.message || 'دسته‌بندی بازیابی شد.'),
                updates.some(x => x.status === 'rejected') ? 'warning' : 'success');
        } catch (e) {
            notice('categoriesMessage', e.message || 'بازیابی انجام نشد.');
        } finally {
            s.busy = false;
            if (s.deletedLoaded) list.render('deleted');
            else { button.disabled = false; button.textContent = 'بازیابی'; }
        }
    }
    function switchPanel(deleted) {
        $('activeCategoriesPanel').hidden = deleted;
        $('deletedCategoriesPanel').hidden = !deleted;
        $('activeCategoriesPanel').classList.toggle('d-none', deleted);
        $('deletedCategoriesPanel').classList.toggle('d-none', !deleted);
        $('showActiveCategoriesButton').className = 'btn ' + (deleted ? 'btn-outline-primary' : 'btn-primary');
        $('showDeletedCategoriesButton').className = 'btn ' + (deleted ? 'btn-primary' : 'btn-outline-primary');
        $('showActiveCategoriesButton').setAttribute('aria-pressed', String(!deleted));
        $('showDeletedCategoriesButton').setAttribute('aria-pressed', String(deleted));
        if (deleted && !s.deletedLoaded) loadDeleted().catch(() => {});
    }
    function initialize() {
        const page = $('categoriesMain');
        if (!page) return;
        if (!api() || !fmt() || !w.bootstrap || !w.TarazinoCategoryForm || !w.TarazinoCategoryList) {
            notice('categoriesMessage', 'یکی از اسکریپت‌های مشترک دسته‌بندی‌ها بارگذاری نشده است.');
            return;
        }
        s.apiUrl = page.dataset.categoriesApiUrl.replace(/\/$/, '');
        const ctx = { s, $, api, fmt, notice, reloadActive,
            edit: cat => form.openEdit(cat), askDelete, restore };
        form = w.TarazinoCategoryForm.create(ctx);
        list = w.TarazinoCategoryList.create(ctx);
        form.bind(); list.bind();
        $('confirmDeleteCategoryButton').addEventListener('click', remove);
        $('showActiveCategoriesButton').addEventListener('click', () => switchPanel(false));
        $('showDeletedCategoriesButton').addEventListener('click', () => switchPanel(true));
        $('deleteCategoryModal').addEventListener('hidden.bs.modal', () => notice('deleteCategoryMessage', ''));
        switchPanel(false);
        loadActive();
    }
    if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})(window, document);
