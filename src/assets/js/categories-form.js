/** TARAZINO | Add/edit category modal; loaded before categories.js. */
(function (w, d) {
    'use strict';
    function create(ctx) {
        const { s, $, api, notice, reloadActive } = ctx;
        let editingId = null;
        const modal = () => w.bootstrap.Modal.getOrCreateInstance($('categoryModal'));

        function fieldError(name, text = '') {
            $(name).classList.toggle('is-invalid', Boolean(text));
            $(name + 'Error').textContent = text;
        }
        function reset() {
            $('categoryForm').reset();
            editingId = null;
            $('categoryModalTitle').textContent = 'افزودن دسته‌بندی';
            fieldError('categoryName');
            fieldError('categoryType');
            notice('categoryFormMessage', '');
        }
        function openNew() { reset(); } // The add button already has data-bs-toggle.
        function openEdit(category) {
            reset();
            editingId = String(category.id);
            $('categoryModalTitle').textContent = 'ویرایش دسته‌بندی';
            $('categoryName').value = category.name || '';
            $('categoryType').value = category.type || '';
            modal().show();
        }
        function validate() {
            const name = $('categoryName').value.trim();
            const type = $('categoryType').value;
            const issues = {
                categoryName: [...name].length >= 1 && [...name].length <= 100
                    ? '' : 'نام باید بین ۱ تا ۱۰۰ نویسه باشد.',
                categoryType: ['income', 'expense'].includes(type)
                    ? '' : 'نوع دسته‌بندی را انتخاب کنید.'
            };
            Object.entries(issues).forEach(([key, text]) => fieldError(key, text));
            if (Object.values(issues).some(Boolean)) return null;
            const data = new FormData();
            data.append('name', name);
            data.append('type', type);
            return data;
        }
        async function save(event) {
            event.preventDefault();
            if (s.busy) return;
            const data = validate();
            if (!data) return;
            const button = $('saveCategoryButton');
            s.busy = true;
            button.disabled = true;
            button.textContent = 'در حال ذخیره...';
            const url = editingId === null ? s.apiUrl + '/store'
                : s.apiUrl + '/' + encodeURIComponent(editingId) + '/update';
            try {
                const result = await api().post(url, data);
                modal().hide();
                notice('categoriesMessage', result.message || 'دسته‌بندی ذخیره شد.', 'success');
                try { await reloadActive(); }
                catch (e) {
                    notice('categoriesMessage', 'ذخیره شد، اما فهرست به‌روز نشد: ' + e.message, 'warning');
                }
            } catch (e) {
                // The current shared API returns a message; do not assume errors[].
                notice('categoryFormMessage', e.message || 'ذخیره انجام نشد.');
            } finally {
                s.busy = false;
                button.disabled = false;
                button.textContent = 'ذخیره';
            }
        }
        function bind() {
            $('addCategoryButton').addEventListener('click', openNew);
            $('categoryForm').addEventListener('submit', save);
            ['categoryName', 'categoryType'].forEach(name => {
                ['input', 'change'].forEach(eventName => {
                    $(name).addEventListener(eventName, () => {
                        fieldError(name);
                        notice('categoryFormMessage', '');
                    });
                });
            });
            $('categoryModal').addEventListener('hidden.bs.modal', reset);
        }
        return { bind, openEdit };
    }
    w.TarazinoCategoryForm = Object.freeze({ create });
})(window, document);
