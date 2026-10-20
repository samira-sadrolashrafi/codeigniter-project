/**
 * TARAZINO | Login and registration.
 * Handles deleted-account recovery through a confirmation modal.
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

            if (form) {
                connect(form, errorFields[id]);
            }
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

            if (box) {
                box.textContent = '';
            }

            const input = document.getElementById(
                fieldIds[id] || id.replace(/_err$/, '')
            );

            if (input) {
                input.classList.remove('is-invalid');
            }
        }

        function showRestoreMessage(text, type = 'danger') {
            const box = document.getElementById('accountRestoreMessage');

            if (!box) return;

            box.textContent = text || '';
            box.className = text ? 'alert alert-' + type : 'alert d-none';
        }

        function openRestoreModal() {
            const modalElement = document.getElementById('accountRestoreModal');

            if (!modalElement || !window.bootstrap || !window.bootstrap.Modal) {
                status('حساب شما حذف شده است، اما نمایش پنجره بازیابی امکان‌پذیر نیست.');
                return;
            }

            showRestoreMessage('');

            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }

        errorIds.forEach(id => {
            const input = document.getElementById(
                fieldIds[id] || id.replace(/_err$/, '')
            );

            if (input) {
                ['input', 'change'].forEach(name => {
                    input.addEventListener(name, () => clear(id));
                });
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

                const { ok, result } = await window.TarazinoAuthRequest.post(
                    form.action,
                    form
                );

                const response = result && typeof result === 'object'
                    ? result : {};

                if (!ok) {
                    if (
                        form.id === 'loginForm' &&
                        response.account_deleted === true
                    ) {
                        if (response.recovery_available === true) {
                            openRestoreModal();
                        } else {
                            status(
                                response.message ||
                                'مهلت بازیابی این حساب به پایان رسیده است.'
                            );
                        }

                        return;
                    }

                    const errors = response.errors &&
                        typeof response.errors === 'object'
                        ? response.errors : {};

                    errorIds.forEach(id => {
                        if (typeof errors[id] !== 'string' || !errors[id]) {
                            return;
                        }

                        const box = document.getElementById(id);

                        if (box) {
                            box.textContent = errors[id];
                        }

                        const input = document.getElementById(
                            fieldIds[id] || id.replace(/_err$/, '')
                        );

                        if (input) {
                            input.classList.add('is-invalid');
                        }
                    });

                    status(response.message || 'درخواست انجام نشد.');
                    return;
                }

                status(response.message || 'عملیات با موفقیت انجام شد.', 'success');

                if (form.dataset.successUrl) {
                    window.setTimeout(() => {
                        window.location.assign(form.dataset.successUrl);
                    }, 800);
                }

            } catch (error) {
                status(error.message || 'درخواست انجام نشد.');
            } finally {
                busy = false;
                submit.disabled = false;
                submit.textContent = original;
            }
        });

        if (form.id === 'loginForm') {
            const modalElement = document.getElementById('accountRestoreModal');
            const restoreButton = document.getElementById('restoreAccountButton');

            if (modalElement && restoreButton) {
                let restoreBusy = false;

                restoreButton.addEventListener('click', async () => {
                    if (restoreBusy) return;

                    const username = form.elements.namedItem('username');
                    const password = form.elements.namedItem('password');
                    const restoreUrl = modalElement.dataset.restoreUrl;

                    showRestoreMessage('');

                    if (!username.value.trim() || !password.value) {
                        showRestoreMessage(
                            'نام کاربری و رمز عبور را در فرم ورود وارد کنید.'
                        );
                        return;
                    }

                    if (!restoreUrl) {
                        showRestoreMessage('مسیر بازیابی حساب تنظیم نشده است.');
                        return;
                    }

                    if (!window.TarazinoAuthRequest) {
                        showRestoreMessage('فایل auth-request.js بارگذاری نشده است.');
                        return;
                    }

                    restoreBusy = true;

                    const original = restoreButton.textContent;
                    restoreButton.disabled = true;
                    restoreButton.textContent = 'در حال بازیابی...';

                    try {
                        const { ok, result } = await window.TarazinoAuthRequest.post(
                            restoreUrl,
                            form
                        );

                        const response = result && typeof result === 'object'
                            ? result : {};

                        if (!ok) {
                            if (
                                response.account_deleted === true &&
                                response.recovery_available === false
                            ) {
                                if (window.bootstrap && window.bootstrap.Modal) {
                                    window.bootstrap.Modal
                                        .getOrCreateInstance(modalElement)
                                        .hide();
                                }

                                status(
                                    response.message ||
                                    'مهلت بازیابی این حساب به پایان رسیده است.'
                                );
                                return;
                            }

                            showRestoreMessage(
                                response.message || 'بازیابی حساب انجام نشد.'
                            );
                            return;
                        }

                        showRestoreMessage(
                            response.message || 'حساب با موفقیت بازیابی شد.',
                            'success'
                        );

                        if (form.dataset.successUrl) {
                            window.setTimeout(() => {
                                window.location.assign(form.dataset.successUrl);
                            }, 800);
                        }

                    } catch (error) {
                        showRestoreMessage(
                            error.message || 'درخواست بازیابی انجام نشد.'
                        );
                    } finally {
                        restoreBusy = false;
                        restoreButton.disabled = false;
                        restoreButton.textContent = original;
                    }
                });
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})(window, document);