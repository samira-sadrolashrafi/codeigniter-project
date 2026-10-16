
/**
 * TARAZINO | Three-step password recovery modal.
 * Username -> security answer -> server nonce -> reset password.
 * No enrollment modal, no auto-login and no client-side authentication.
 */
(function (window, document) {
    'use strict';

    function start() {
        const modal = document.getElementById('recoveryModal');
        if (!modal) return;

        const $ = id => document.getElementById(id);
        const forms = [
            $('recoveryQuestionForm'),
            $('recoveryVerifyForm'),
            $('recoveryResetForm')
        ];
        const restart = $('recoveryStartOver');
        const notice = $('recoveryMessage');
        if (forms.some(form => !form) || !restart || !notice) return;

        let busy = false;

        function message(text, kind = 'danger') {
            notice.textContent = text || '';
            notice.className = text ? 'alert alert-' + kind : 'alert d-none';
        }

        function showStep(index) {
            forms.forEach((form, i) => form.classList.toggle('d-none', i !== index));
            restart.classList.toggle('d-none', index === 0);
        }

        function clearPasswordErrors() {
            $('recovery_password_err').textContent = '';
            $('recovery_confirm_err').textContent = '';
        }

        function reset() {
            forms.forEach(form => form.reset());
            $('recoveryNonce').value = '';
            $('recoveryQuestionLabel').textContent = '';
            clearPasswordErrors();
            message('');
            showStep(0);
        }

        async function submit(form, handler) {
            form.addEventListener('submit', async event => {
                event.preventDefault();
                if (busy) return;
                const button = form.querySelector('[type="submit"]');
                const oldLabel = button.textContent;
                busy = true;
                button.disabled = true;
                button.textContent = 'در حال بررسی...';
                message('');
                try {
                    if (!window.TarazinoAuthRequest) {
                        throw new Error('فایل auth-request.js بارگذاری نشده است.');
                    }
                    await handler();
                } catch (error) {
                    message(error.message || 'درخواست انجام نشد.');
                } finally {
                    busy = false;
                    button.disabled = false;
                    button.textContent = oldLabel;
                }
            });
        }

        restart.addEventListener('click', reset);
        modal.addEventListener('hidden.bs.modal', reset);
        modal.addEventListener('show.bs.modal', () => {
            reset();
            const username = $('username');
            if (username && username.value.trim()) {
                $('recoveryUsername').value = username.value.trim();
            }
        });

        submit(forms[0], async () => {
            const username = $('recoveryUsername');
            username.value = username.value.trim();
            if (!/^[A-Za-z0-9_]{3,100}$/.test(username.value)) {
                message('نام کاربری معتبر وارد کنید.');
                username.focus();
                return;
            }
            const { ok, result } = await window.TarazinoAuthRequest.post(modal.dataset.questionUrl, forms[0]);
            if (!ok) return message(result.message || 'دریافت سؤال انجام نشد.');
            if (typeof result.question !== 'string' || !result.question) {
                throw new Error('سؤال امنیتی از سرور دریافت نشد.');
            }
            $('recoveryQuestionLabel').textContent = result.question;
            showStep(1);
            $('recoveryAnswer').focus();
        });

        submit(forms[1], async () => {
            const answer = $('recoveryAnswer');
            if (!answer.value.trim()) {
                message('پاسخ سؤال امنیتی را وارد کنید.');
                answer.focus();
                return;
            }
            const { ok, result } = await window.TarazinoAuthRequest.post(modal.dataset.verifyUrl, forms[1]);
            if (!ok) return message(result.message || 'پاسخ تأیید نشد.');
            if (typeof result.nonce !== 'string' || !result.nonce) {
                throw new Error('مجوز بازیابی از سرور دریافت نشد.');
            }
            $('recoveryNonce').value = result.nonce;
            answer.value = '';
            showStep(2);
            message('پاسخ تأیید شد. رمز عبور جدید را تعیین کنید.', 'success');
        });

        submit(forms[2], async () => {
            clearPasswordErrors();
            const password = $('recoveryNewPassword').value;
            const confirmation = $('recoveryConfirmPassword').value;
            if (!password || password !== confirmation) {
                $('recovery_confirm_err').textContent = 'رمز جدید و تکرار آن باید یکسان باشند.';
                return;
            }
            const { ok, result } = await window.TarazinoAuthRequest.post(modal.dataset.resetUrl, forms[2]);
            if (!ok) {
                const errors = result.errors && typeof result.errors === 'object' ? result.errors : {};
                ['recovery_password_err', 'recovery_confirm_err'].forEach(id => {
                    if (typeof errors[id] === 'string') $(id).textContent = errors[id];
                });
                return message(result.message || 'ذخیره رمز جدید انجام نشد.');
            }
            reset();
            message('رمز عبور تغییر کرد. پنجره را ببندید و با رمز جدید وارد شوید.', 'success');
        });

        showStep(0);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else start();
})(window, document);
