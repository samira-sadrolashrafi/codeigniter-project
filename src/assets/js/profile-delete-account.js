/** TARAZINO | Delete-account modal controller. */
(function (w, d) {
    'use strict';

    const byId = id => d.getElementById(id);

    function initialize() {
        const root = byId('profileMain');
        const form = byId('deleteAccountForm');
        const modalElement = byId('deleteAccountModal');

        if (!root || !form || !modalElement) return;

        const endpoint = (root.dataset.deleteAccountApiUrl || '').trim();
        const loginUrl = (root.dataset.loginUrl || '').trim();
        const password = byId('deleteAccountPassword');
        const confirmation = byId('deleteAccountConfirmation');
        const submitButton = byId('deleteAccountSubmitButton');
        const messageBox = byId('deleteAccountMessage');
        const passwordFeedback = byId('deleteAccountPasswordFeedback');

        let submitting = false;

        function showMessage(text, type = 'danger') {
            messageBox.textContent = text;
            messageBox.className = text
                ? 'alert alert-' + type + ' mt-3 mb-0'
                : 'alert d-none mt-3 mb-0';
        }

        function showPasswordError(text) {
            password.classList.toggle('is-invalid', Boolean(text));
            passwordFeedback.textContent = text || '';
            passwordFeedback.classList.toggle('d-none', !text);
        }

        password.addEventListener('input', function () {
            showPasswordError('');
            showMessage('');
        });

        confirmation.addEventListener('change', function () {
            showMessage('');
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (submitting) return;

            if (!password.value) {
                showPasswordError('برای حذف حساب، وارد کردن رمز فعلی الزامی است.');
                password.focus();
                return;
            }

            showPasswordError('');

            if (!confirmation.checked) {
                showMessage('برای ادامه، تأیید حذف حساب را علامت بزنید.');
                confirmation.focus();
                return;
            }

            if (!endpoint) {
                showMessage('اطلاعات لازم برای ارسال درخواست موجود نیست. صفحه را تازه‌سازی کنید.');
                return;
            }

            if (!w.TarazinoApi || typeof w.TarazinoApi.post !== 'function') {
                showMessage('فایل api.js بارگذاری نشده است. صفحه را تازه‌سازی کنید.');
                return;
            }

            submitting = true;
            submitButton.disabled = true;
            submitButton.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>در حال ارسال درخواست...';

            try {
                const result = await w.TarazinoApi.post(
                    endpoint,
                    new FormData(form)
                );

                showMessage(result.message || 'حساب کاربری حذف شد.', 'success');

                const modal = w.bootstrap && w.bootstrap.Modal
                    ? w.bootstrap.Modal.getOrCreateInstance(modalElement)
                    : null;

                if (modal) modal.hide();

                w.location.assign(loginUrl || '/login');
            } catch (error) {
                const text = error && typeof error.message === 'string'
                    ? error.message
                    : 'حذف حساب انجام نشد. دوباره تلاش کنید.';

                showMessage(text);
            } finally {
                submitting = false;
                submitButton.disabled = false;
                submitButton.innerHTML =
                    '<i class="fa-regular fa-trash-can me-1" aria-hidden="true"></i> تأیید و حذف حساب';
            }
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            form.reset();
            password.classList.remove('is-invalid');
            showPasswordError('');
            showMessage('');
        });
    }

    if (d.readyState === 'loading') {
        d.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})(window, document);
