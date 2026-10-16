<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tarazino | Registration page (CodeIgniter 3).
 *
 * Bootstrap-first. Uses the same branding as auth/login.php and Font Awesome
 * icon fonts; form submission and server errors remain in assets/js/auth.js.
 *
 * No user-selectable category colors/icons and no registration DB queries here.
 * This presentational view can later be migrated to a Vue 3 RegisterView.
 *
 * Stable contract with auth.js and Auth_api::register():
 *  - registerForm / registerMessage
 *  - first_name, last_name, username, email, password, password_confirm,
 *    question_id, security_answer
 *  - all eight *_err containers below
 */
$recovery_questions = isset($questions) && is_array($questions) ? $questions : array();

$this->load->view('layouts/header', array(
    'page_title' => 'ایجاد حساب کاربری',
    'page_styles' => array(base_url('assets/css/dashboard.css'))
));
?>

<main class="container-fluid min-vh-100 bg-body-tertiary py-4 py-lg-5" id="registerPage" data-component="register-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-11 col-xl-10">
                <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
                    <div class="row g-0">

                        <!-- RTL: registration form on the right; information on the left. -->
                        <div class="col-12 col-lg-8">
                            <section class="card-body p-4 p-md-5" aria-labelledby="registerPageTitle">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <!-- Brand mark matches auth/login.php (calculator + three bars). -->
                                    <svg class="tz-brand-logo" viewBox="0 0 48 48" width="48" height="48"
                                         aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="tzMarkRegister" x1="0" y1="0" x2="1" y2="1">
                                                <stop offset="0" stop-color="#132D56"/>
                                                <stop offset="1" stop-color="#174B96"/>
                                            </linearGradient>
                                        </defs>
                                        <rect x="1" y="1" width="46" height="46" rx="12" fill="url(#tzMarkRegister)"/>
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

                                <h1 class="h3 fw-bold mb-2" id="registerPageTitle">ایجاد حساب کاربری</h1>
                                <p class="text-body-secondary mb-4">اطلاعاتت رو وارد کن تا حساب مالی شخصی خودت رو بسازی.</p>

                                <div id="registerMessage" class="alert d-none" role="alert"
                                     aria-live="polite" aria-atomic="true"></div>

                                <form id="registerForm"
                                      action="<?php echo html_escape(site_url('auth/register')); ?>"
                                      method="post"
                                      data-success-url="<?php echo html_escape(site_url('login')); ?>"
                                      novalidate>
                                    <div class="row g-3">
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label fw-medium" for="first_name">نام</label>
                                            <input class="form-control" type="text" id="first_name" name="first_name"
                                                   placeholder="نام" autocomplete="given-name" maxlength="100"
                                                   aria-describedby="first_name_err" required>
                                            <div class="text-danger small mt-1" id="first_name_err" aria-live="polite"></div>
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label fw-medium" for="last_name">نام خانوادگی</label>
                                            <input class="form-control" type="text" id="last_name" name="last_name"
                                                   placeholder="نام خانوادگی" autocomplete="family-name" maxlength="100"
                                                   aria-describedby="last_name_err" required>
                                            <div class="text-danger small mt-1" id="last_name_err" aria-live="polite"></div>
                                        </div>

                                        <div class="col-12 col-sm-6">
                                            <label class="form-label fw-medium" for="username">نام کاربری</label>
                                            <div class="input-group">
                                                <span class="input-group-text" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                                                <input class="form-control" type="text" id="username" name="username"
                                                       placeholder="مثلاً: my_account" autocomplete="username"
                                                       dir="ltr" maxlength="100" minlength="3"
                                                       aria-describedby="usernameHelp username_err" required>
                                            </div>
                                            <div id="usernameHelp" class="form-text">حداقل ۳ کاراکتر انگلیسی، عدد یا زیرخط (_). نام کاربری باید یکتا باشه.</div>
                                            <div class="text-danger small mt-1" id="username_err" aria-live="polite"></div>
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label fw-medium" for="email">ایمیل</label>
                                            <div class="input-group">
                                                <span class="input-group-text" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>
                                                <input class="form-control" type="email" id="email" name="email"
                                                       placeholder="you@example.com" autocomplete="email"
                                                       dir="ltr" aria-describedby="email_err" required>
                                            </div>
                                            <div class="text-danger small mt-1" id="email_err" aria-live="polite"></div>
                                        </div>

                                        <div class="col-12 col-sm-6">
                                            <label class="form-label fw-medium" for="password">رمز عبور</label>
                                            <div class="input-group">
                                                <span class="input-group-text" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                                                <input class="form-control" type="password" id="password" name="password"
                                                       placeholder="رمز عبور" autocomplete="new-password" dir="ltr"
                                                       minlength="8" aria-describedby="passwordHelp password_err" required>
                                            </div>
                                            <div class="text-danger small mt-1" id="password_err" aria-live="polite"></div>
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label fw-medium" for="password_confirm">تکرار رمز عبور</label>
                                            <div class="input-group">
                                                <span class="input-group-text" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></span>
                                                <input class="form-control" type="password" id="password_confirm"
                                                       name="password_confirm" placeholder="تکرار رمز عبور"
                                                       autocomplete="new-password" dir="ltr"
                                                       aria-describedby="confirm_password_err" required>
                                            </div>
                                            <div class="text-danger small mt-1" id="confirm_password_err" aria-live="polite"></div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-text mt-0" id="passwordHelp">
                                                رمز عبور باید حداقل ۸ کاراکتر و شامل حرف بزرگ و کوچک انگلیسی، عدد و کاراکتر خاص (مثل @ یا #) باشه.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border-top mt-4 pt-4">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="fa-solid fa-key text-primary" aria-hidden="true"></i>
                                            <h2 class="h6 fw-bold mb-0">بازیابی حساب</h2>
                                        </div>
                                        <p class="small text-body-secondary mb-3">
                                            برای زمانی که رمز عبورت رو فراموش می‌کنی، یک سؤال انتخاب کن و پاسخی بده که فقط خودت می‌دونی.
                                        </p>

                                        <div class="mb-3">
                                            <label class="form-label fw-medium" for="question_id">سؤال امنیتی</label>
                                            <select class="form-select" id="question_id" name="question_id"
                                                    aria-describedby="question_id_err" required>
                                                <option value="">یک سؤال انتخاب کن</option>
                                                <?php foreach ($recovery_questions as $question_id => $question_label): ?>
                                                    <option value="<?php echo (int) $question_id; ?>">
                                                        <?php echo html_escape($question_label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="text-danger small mt-1" id="question_id_err" aria-live="polite"></div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label fw-medium" for="security_answer">پاسخ امنیتی</label>
                                            <div class="input-group">
                                                <span class="input-group-text" aria-hidden="true"><i class="fa-solid fa-user-shield"></i></span>
                                                <input class="form-control" type="password" id="security_answer"
                                                       name="security_answer" placeholder="پاسخی که خودت به خاطر می‌سپری"
                                                       autocomplete="off" minlength="6" maxlength="256"
                                                       aria-describedby="securityAnswerHelp security_answer_err" required>
                                            </div>
                                            <div class="text-danger small mt-1" id="security_answer_err" aria-live="polite"></div>
                                            <div id="securityAnswerHelp" class="form-text">
                                                پاسخ باید دست‌کم ۶ کاراکتر باشه. برای امنیت بیشتر از پاسخ قابل‌حدس استفاده نکن.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- auth.js replaces textContent while submitting; keep plain text. -->
                                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">ایجاد حساب کاربری</button>
                                </form>

                                <div class="text-center border-top pt-3 mt-4">
                                    <span class="text-body-secondary">قبلاً ثبت‌نام کردی؟</span>
                                    <a class="fw-semibold text-decoration-none ms-1"
                                       href="<?php echo html_escape(site_url('login')); ?>">وارد شو</a>
                                </div>
                            </section>
                        </div>

                        <div class="col-lg-4 d-none d-lg-flex dashboard-sidebar-wrap">
                            <aside class="text-white d-flex flex-column justify-content-center p-4 p-xl-5 w-100"
                                   aria-label="معرفی ثبت‌نام در ترازینو">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-4 border
                                            border-white border-opacity-25 p-3 mb-4 align-self-start">
                                    <i class="fa-solid fa-shield-heart fa-2x" aria-hidden="true"></i>
                                </div>
                                <h2 class="h4 fw-bold lh-base mb-3">شروعی ساده برای مدیریت مالی</h2>
                                <p class="text-white-50 lh-lg mb-4">
                                    تراکنش‌هات رو منظم ثبت کن، دسته‌بندی‌های مالی بساز و تصویر روشنی از وضعیت حسابت داشته باش.
                                </p>
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-chart-line fa-fw" aria-hidden="true"></i>
                                        <span>داشبورد مالی یکپارچه</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-list-check fa-fw" aria-hidden="true"></i>
                                        <span>ثبت و مدیریت تراکنش‌ها</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-key fa-fw" aria-hidden="true"></i>
                                        <span>بازیابی حساب با سؤال امنیتی</span>
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

<?php
// Bootstrap is loaded before the page-specific script by the shared footer.
$this->load->view('layouts/footer', array(
    'page_scripts' => array(
        base_url('assets/js/auth-request.js'),
        base_url('assets/js/auth.js')
    )
));
?>
