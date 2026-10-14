(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('logoutConfirmModal');
        var confirmButton = document.getElementById('confirmLogoutButton');
        var message = document.getElementById('logoutMessage');

        if (!modal || !confirmButton || !message) {
            return;
        }

        var defaultButtonText = confirmButton.textContent.trim();

        function showError(text) {
            message.textContent = text;
            message.classList.remove('d-none');
        }

        function clearError() {
            message.textContent = '';
            message.classList.add('d-none');
        }

        modal.addEventListener('show.bs.modal', clearError);
        document.querySelectorAll('[data-sidebar-logout]').forEach(function (button) {
            button.addEventListener('click', function () {
                var offcanvas = button.closest('.offcanvas');
                if (offcanvas) offcanvas.dataset.openLogout = 'true';
            });
        });
        document.querySelectorAll('.offcanvas').forEach(function (offcanvas) {
            offcanvas.addEventListener('hidden.bs.offcanvas', function () {
                if (offcanvas.dataset.openLogout === 'true') {
                    offcanvas.dataset.openLogout = 'false';
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        });
        confirmButton.addEventListener('click', async function () {
            if (confirmButton.disabled) {
                return;
            }

            clearError();
            confirmButton.disabled = true;
            confirmButton.textContent = 'در حال خروج...';

            try {
                var response = await fetch(modal.dataset.logoutUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' }
                });
                var responseText = await response.text();
                var result;

                try {
                    result = JSON.parse(responseText);
                } catch (error) {
                    throw new Error('پاسخ سرور قابل خواندن نیست. لطفاً دوباره تلاش کنید.');
                }

                if (!response.ok || !result || result.success !== true) {
                    showError(result && result.message ? result.message : 'خروج از حساب انجام نشد. لطفاً دوباره تلاش کنید.');
                    return;
                }

                window.location.assign(modal.dataset.loginUrl);
            } catch (error) {
                showError(error.message || 'ارتباط با سرور برقرار نشد. اتصال خود را بررسی و دوباره تلاش کنید.');
            } finally {
                confirmButton.disabled = false;
                confirmButton.textContent = defaultButtonText;
            }
        });
    });
}());
