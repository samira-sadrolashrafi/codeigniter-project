<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tarazino | Login page (CodeIgniter 3).
 *
 * Bootstrap-first markup, Font Awesome icon fonts, no page-specific JS/CSS.
 * All IDs, input names, API URLs and recovery modals stay compatible
 * with assets/js/auth.js and assets/js/recovery.js.
 *
 * The presentation can later become a Vue LoginView component; API behavior
 * remains outside this view.
 */
$this->load->view('layouts/header', array(
    'page_title' => 'ورود به حساب کاربری',
    'page_styles' => array(base_url('assets/css/dashboard.css'))
));
?>

<main class="container-fluid bg-body-tertiary min-vh-100 d-flex align-items-center py-4 py-lg-5" id="loginPage">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-11 col-xl-10 col-xxl-9">
                <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
                    <div class="row g-0">
                        <!-- Login form comes first: it appears on the right in RTL. -->
                        <div class="col-12 col-lg-6">
                            <section class="card-body p-4 p-md-5" aria-labelledby="loginPageTitle">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <!-- Same calculator + three bars mark as the desktop sidebar. -->
                                    <svg class="tz-brand-logo" viewBox="0 0 48 48" width="48" height="48"
                                         aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="tzMarkLogin" x1="0" y1="0" x2="1" y2="1">
                                                <stop offset="0" stop-color="#132D56"/>
                                                <stop offset="1" stop-color="#174B96"/>
                                            </linearGradient>
                                        </defs>
                                        <rect x="1" y="1" width="46" height="46" rx="12" fill="url(#tzMarkLogin)"/>
                                        <rect x="7" y="9" width="18" height="7" rx="2.4" fill="#DAECFF"/>
                                        <rect x="7" y="21" width="6" height="6" rx="1.6" fill="#BBD9FF"/>
                                        <rect x="17" y="21" width="6" height="6" rx="1.6" fill="#BBD9FF"/>
                                        <rect x="7" y="31" width="6" height="6" rx="1.6" fill="#BBD9FF"/>
                                        <rect x="17" y="31" width="6" height="6" rx="1.6" fill="#BBD9FF"/>
                                        <rect x="27" y="28" width="5" height="10" rx="2" fill="#42A5F5"/>
                                        <rect x="34" y="21" width="5" height="17" rx="2" fill="#55C5ED"/>
                                        <rect x="41" y="14" width="4" height="24" rx="2" fill="#41D4B4"/>
                                    </svg>
                                    <div>
                                        <p class="fw-bold fs-4 lh-sm mb-0">ترازینو</p>
                                        <small class="text-body-secondary">مدیریت مالی شخصی</small>
                                    </div>
                                </div>

                                <h1 class="h3 fw-bold mb-2" id="loginPageTitle">ورود به حساب کاربری</h1>
                                <p class="text-body-secondary mb-4">نام کاربری و رمز عبورت رو وارد کن.</p>

                                <div id="loginMessage" class="alert d-none" role="alert"
                                     aria-live="polite" aria-atomic="true"></div>

                                <form id="loginForm"
                                      action="<?php echo html_escape(site_url('auth/login')); ?>"
                                      method="post"
                                      data-success-url="<?php echo html_escape(site_url('dashboard')); ?>"
                                      novalidate>

                                    <div class="mb-3">
                                        <label for="username" class="form-label fw-medium">نام کاربری</label>
                                        <div class="input-group">
                                            <span class="input-group-text" aria-hidden="true">
                                                <i class="fa-solid fa-user"></i>
                                            </span>
                                            <input type="text" id="username" name="username"
                                                   class="form-control" placeholder="نام کاربری"
                                                   autocomplete="username" maxlength="100"
                                                   aria-describedby="username_err" required>
                                        </div>
                                        <div id="username_err" class="text-danger small mt-1"
                                             aria-live="polite"></div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                            <label for="password" class="form-label fw-medium mb-0">رمز عبور</label>
                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0 text-decoration-none"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#recoveryModal">
                                                فراموشی رمز؟
                                            </button>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text" aria-hidden="true">
                                                <i class="fa-solid fa-lock"></i>
                                            </span>
                                            <input type="password" id="password" name="password"
                                                   class="form-control" placeholder="رمز عبور"
                                                   autocomplete="current-password" dir="ltr"
                                                   aria-describedby="password_err" required>
                                        </div>
                                        <div id="password_err" class="text-danger small mt-1"
                                             aria-live="polite"></div>
                                    </div>

                                    <div class="form-check mb-4">
                                        <input type="checkbox" class="form-check-input"
                                               id="remember" name="remember" value="1">
                                        <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                                        ورود به حساب
                                    </button>
                                </form>

                                <div class="text-center mt-4 pt-3 border-top">
                                    <span class="text-body-secondary">حساب کاربری نداری؟</span>
                                    <a class="fw-semibold text-decoration-none ms-1"
                                       href="<?php echo html_escape(site_url('register')); ?>">ثبت‌نام کن</a>
                                </div>
                            </section>
                        </div>

                        <!-- Supporting brand panel; hidden on mobile to prioritize the form. -->
                        <div class="col-lg-6 d-none d-lg-flex dashboard-sidebar-wrap">
                            <aside class="text-white p-4 p-xl-5 d-flex flex-column justify-content-center w-100"
                                   aria-label="معرفی ترازینو">
                                <div class="mb-4">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-4
                                                 border border-white border-opacity-25 p-3 mb-4">
                                        <i class="fa-solid fa-chart-line fa-2x" aria-hidden="true"></i>
                                    </span>
                                    <h2 class="h3 fw-bold lh-base mb-3">مدیریت مالی، ساده و روشن</h2>
                                    <p class="text-white-50 lh-lg mb-0">
                                        درآمدها، هزینه‌ها و موجودی حسابت رو در یک فضای منظم مدیریت کن.
                                    </p>
                                </div>
                                <div class="d-flex flex-column gap-3 mt-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="fa-solid fa-arrow-right-arrow-left fa-fw" aria-hidden="true"></i>
                                        <span>ثبت و پیگیری تراکنش‌ها</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="fa-solid fa-tags fa-fw" aria-hidden="true"></i>
                                        <span>مدیریت دسته‌بندی‌های مالی</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="fa-solid fa-chart-pie fa-fw" aria-hidden="true"></i>
                                        <span>نمای کلی وضعیت مالی</span>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Confirmation modal for restoring a deleted account. -->
<div class="modal fade" id="accountRestoreModal" tabindex="-1"
     aria-labelledby="accountRestoreModalTitle" aria-hidden="true"
     data-restore-url="<?php echo html_escape(site_url('auth/account/restore')); ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header">
                <h2 class="modal-title fs-5 fw-bold" id="accountRestoreModalTitle">
                    بازیابی حساب کاربری
                </h2>
                <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"
                        aria-label="بستن"></button>
            </div>

            <div class="modal-body">
                <p class="mb-3">
                    این حساب قبلاً حذف شده است، اما هنوز در مهلت ۳۰ روزه بازیابی قرار دارد.
                </p>
                <p class="text-body-secondary mb-3">
                    با تأیید بازیابی، حساب شما دوباره فعال می‌شود و وارد آن خواهید شد.
                </p>

                <div id="accountRestoreMessage" class="alert d-none"
                     role="alert" aria-live="polite" aria-atomic="true"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">
                    انصراف
                </button>
                <button type="button" class="btn btn-primary"
                        id="restoreAccountButton">
                    بازیابی حساب و ورود
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Existing password recovery remains unchanged. -->
<?php $this->load->view('auth/recovery_modal'); ?>

<?php
// Bootstrap is loaded by footer before these DOMContentLoaded form handlers.
$this->load->view('layouts/footer', array(
    'page_scripts' => array(
        base_url('assets/js/auth-request.js'),
        base_url('assets/js/auth.js'),
        base_url('assets/js/recovery.js')
    )
));
?>