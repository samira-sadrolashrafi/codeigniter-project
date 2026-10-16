
/**
 * TARAZINO | Login and registration.
 * Uses AuthRequest to retain validation errors returned by Auth_api.
 * Logout is managed ONLY by the existing logout.js.
 */
(function (window, document) {
    'use strict';

    const errorFields = {
        loginForm: ['username_err', 'password_err'],
        registerForm: [
            'first_name_err', 'last_name_err', 'username_err', 'email_err',
            'password_err', 'confirm_password_err',
            'question_id_err', 'security_answer_err'
        ]
    };
    const fieldIds = { confirm_password_err: 'password_confirm' };

    function start() {
        Object.keys(errorFields).forEach(id => {
            const form = document.getElementById(id);
            if (form) connect(form, errorFields[id]);
        });
    }

    function connect(form, errorIds) {
        const message = document.getElementById(
            form.id === 'loginForm' ? 'loginMessage' : 'registerMessage'
        );
        const submit = form.querySelector('[type="submit"]');
        if (!message || !submit) return;

        function status(text, type = 'danger') {
            message.textContent = text || '';
            message.className = text ? 'alert alert-' + type : 'alert d-none';
        }

        function clear(id) {
            const box = document.getElementById(id);
            if (box) box.textContent = '';
            const input = document.getElementById(fieldIds[id] || id.replace(/_err$/, ''));
            if (input) input.classList.remove('is-invalid');
        }

        errorIds.forEach(id => {
            const input = document.getElementById(fieldIds[id] || id.replace(/_err$/, ''));
            if (input) {
                ['input', 'change'].forEach(name => input.addEventListener(name, () => clear(id)));
            }
        });

        let busy = false;
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (busy) return;
            errorIds.forEach(clear);
            status('');

            if (form.id === 'registerForm') {
                const password = form.elements.namedItem('password');
                const confirm = form.elements.namedItem('password_confirm');
                if (password.value !== confirm.value) {
                    document.getElementById('confirm_password_err').textContent =
                        'تکرار رمز عبور مطابقت ندارد.';
                    confirm.classList.add('is-invalid');
                    confirm.focus();
                    return;
                }
            }

            busy = true;
            const original = submit.textContent;
            submit.disabled = true;
            submit.textContent = 'در حال بررسی...';
            try {
                if (!window.TarazinoAuthRequest) {
                    throw new Error('فایل auth-request.js بارگذاری نشده است.');
                }
                const { ok, result } = await window.TarazinoAuthRequest.post(form.action, form);
                if (!ok) {
                    const errors = result.errors && typeof result.errors === 'object'
                        ? result.errors : {};
                    errorIds.forEach(id => {
                        if (typeof errors[id] !== 'string' || !errors[id]) return;
                        const box = document.getElementById(id);
                        if (box) box.textContent = errors[id];
                        const input = document.getElementById(fieldIds[id] || id.replace(/_err$/, ''));
                        if (input) input.classList.add('is-invalid');
                    });
                    status(result.message || 'درخواست انجام نشد.');
                    return;
                }
                status(result.message || 'عملیات با موفقیت انجام شد.', 'success');
                if (form.dataset.successUrl) {
                    window.setTimeout(() => window.location.assign(form.dataset.successUrl), 800);
                }
            } catch (error) {
                status(error.message || 'درخواست انجام نشد.');
            } finally {
                busy = false;
                submit.disabled = false;
                submit.textContent = original;
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else start();
})(window, document);
