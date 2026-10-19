/** TARAZINO | Add/edit transaction modal. Loaded before transactions.js. */
(function (w, d) {
    'use strict';
    function create(ctx) {
        const { s, $, fmt, api, notice, categoryOf, reloadActive } = ctx;
        let editingId = null;
        const field = name => $('transaction' + name);
        const modal = () => w.bootstrap.Modal.getOrCreateInstance($('transactionModal'));

        function error(name, text = '') {
            const input = field(name);
            const feedback = $('transaction' + name + 'Error');
            if (input) input.classList.toggle('is-invalid', !!text);
            if (feedback) feedback.textContent = text;
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
            const modalElement = $('transactionModal');
            if (modalElement && w.TarazinoDatepicker) {
                w.TarazinoDatepicker.refresh(modalElement);
            }
        }
        function reset() {
            $('transactionForm').reset();
            editingId = null;
            $('transactionModalTitle').textContent = 'افزودن تراکنش';
            notice('transactionFormMessage', '');
            ['Title', 'Type', 'Amount', 'Category', 'Date'].forEach(name => error(name));
            categoryOptions();
            refreshDate();
        }
        function open(tx = null) {
            reset();
            if (tx) {
                editingId = String(tx.id);
                $('transactionModalTitle').textContent = 'ویرایش تراکنش';
                field('Title').value = tx.title || '';
                field('Amount').value = fmt().groupDigits(tx.amount == null ? '' : tx.amount);
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
            refreshDate();
            modal().show();
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
            Object.entries({
                title, category_id: categoryId, amount, transaction_date: date,
                description: field('Description').value
            }).forEach(([key, value]) => form.append(key, value));
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
        function amountKeydown(event) {
            if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) return;
            if (/^[0-9]$/.test(event.key)) return;
            if (event.key === '.') {
                const input = event.currentTarget;
                const remaining = input.value.slice(0, input.selectionStart) + input.value.slice(input.selectionEnd);
                const integerBeforeCursor = fmt().normalizeInputNumber(input.value.slice(0, input.selectionStart)).split('.')[0];
                if (!remaining.includes('.') && /[0-9]/.test(integerBeforeCursor)) return;
            }
            event.preventDefault();
        }
        function amountPaste(event) {
            event.preventDefault();
            const input = event.currentTarget;
            const clipboard = event.clipboardData || w.clipboardData;
            const pasted = clipboard ? clipboard.getData('text') : '';
            const normalized = fmt().normalizeInputNumber(pasted).trim();
            if (!/^\d+(?:\.\d{1,2})?$/.test(normalized)) {
                error('Amount', 'مبلغ کپی‌شده معتبر نیست؛ فقط عدد مثبت با حداکثر دو رقم اعشار وارد کنید.');
                return;
            }
            const start = input.selectionStart, end = input.selectionEnd;
            const result = fmt().formatAmountInput(
                input.value.slice(0, start) + normalized + input.value.slice(end),
                start + normalized.length
            );
            input.value = result.text;
            input.setSelectionRange(result.caret, result.caret);
            error('Amount');
        }
        function bind() {
            $('transactionForm').addEventListener('submit', save);
            field('Type').addEventListener('change', () => { error('Type'); categoryOptions(); });
            ['Title', 'Type', 'Amount', 'Category', 'Date'].forEach(name => {
                const input = field(name);
                if (name === 'Amount') {
                    input.addEventListener('keydown', amountKeydown);
                    input.addEventListener('paste', amountPaste);
                }
                input.addEventListener('input', () => {
                    if (name === 'Amount') {
                        const result = fmt().formatAmountInput(input.value, input.selectionStart);
                        input.value = result.text;
                        input.setSelectionRange(result.caret, result.caret);
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