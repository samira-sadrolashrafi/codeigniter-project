<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tarazino | Shared sign-out confirmation (CodeIgniter 3 / Bootstrap 5.3 RTL).
 *
 * Presentation-only view, ready to become a Vue 3 LogoutConfirmModal component.
 * Existing assets/js/logout.js depends on the following contract:
 *   #logoutConfirmModal     Bootstrap modal element
 *   data-logout-url         POST destination
 *   data-login-url          Destination after successful logout
 *   #confirmLogoutButton    Submit/logout trigger
 *   #logoutMessage          Server/network error message
 *
 * Do not change these IDs or data attributes without updating logout.js.
 * The confirm button intentionally contains text only: logout.js replaces its
 * textContent while a request is in progress.
 */
?>
<div
    class="modal fade tz-modal"
    id="logoutConfirmModal"
    tabindex="-1"
    aria-labelledby="logoutConfirmTitle"
    aria-describedby="logoutConfirmDescription"
    aria-hidden="true"
    data-logout-url="<?php echo html_escape(site_url('auth/logout')); ?>"
    data-login-url="<?php echo html_escape(site_url('login')); ?>"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h2 class="modal-title fs-5 fw-bold" id="logoutConfirmTitle">
                    تأیید خروج از حساب
                </h2>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="بستن پنجره"
                ></button>
            </div>

            <div class="modal-body px-4 py-3">
                <div class="d-flex align-items-start gap-3">
                    <span
                        class="d-inline-flex align-items-center justify-content-center flex-shrink-0 rounded-3 bg-danger-subtle text-danger p-3 fs-4"
                        aria-hidden="true"
                    >
                        <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                    </span>

                    <div class="pt-1">
                        <p class="fw-semibold mb-2" id="logoutConfirmDescription">
                            آیا مطمئنی می‌خواهی از حساب کاربری خارج شوی؟
                        </p>
                        <p class="small text-body-secondary mb-0">
                            برای استفاده دوباره از ترازینو باید وارد حساب خود شوی.
                        </p>
                    </div>
                </div>

                <div
                    id="logoutMessage"
                    class="alert alert-danger d-none mt-3 mb-0"
                    role="alert"
                    aria-live="polite"
                ></div>
            </div>

            <div class="modal-footer border-0 px-4 pt-0 pb-4 gap-2">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal"
                >
                    انصراف
                </button>
                <button
                    type="button"
                    id="confirmLogoutButton"
                    class="btn btn-danger"
                >
                    بله، خارج شو
                </button>
            </div>
        </div>
    </div>
</div>