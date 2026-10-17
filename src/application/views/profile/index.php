<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tarazino | Profile page (presentation layer, CodeIgniter 3).
 *
 * Bootstrap 5.3 RTL handles the layout, cards, inputs, modal and color modes.
 * User icons come from Font Awesome, loaded by layouts/header.php.
 * Future Vue components: ProfilePage, AccountDetails, AvatarPicker,
 * PasswordSettings. No data queries or API writes here.
 *
 * Future controller may pass $profile (array or object) with:
 * first_name, last_name, username, email, avatar, question_id.
 * This view intentionally does not query the DB or simulate a successful save.
 */

$profile_data = array();
if (isset($profile) && (is_array($profile) || is_object($profile))) {
    $profile_data = (array) $profile;
} elseif (isset($user) && (is_array($user) || is_object($user))) {
    $profile_data = (array) $user;
}

$session_username = isset($username) && is_string($username) ? trim($username) : '';
if ($session_username === '' && isset($this->session)) {
    $session_value = $this->session->userdata('username');
    $session_username = is_string($session_value) ? trim($session_value) : '';
}

$profile_first_name = isset($profile_data['first_name']) && is_string($profile_data['first_name'])
    ? $profile_data['first_name'] : '';
$profile_last_name = isset($profile_data['last_name']) && is_string($profile_data['last_name'])
    ? $profile_data['last_name'] : '';
$profile_username = isset($profile_data['username']) && is_string($profile_data['username'])
    ? $profile_data['username'] : $session_username;
$profile_email = isset($profile_data['email']) && is_string($profile_data['email'])
    ? $profile_data['email'] : '';
$profile_question_id = isset($profile_data['question_id'])
    ? (string) $profile_data['question_id'] : '';

// Use the same avatar URL contract as layouts/app_header.php.
$avatar_value = isset($profile_data['avatar']) && is_string($profile_data['avatar'])
    ? trim($profile_data['avatar']) : '';
if ($avatar_value === '' && isset($this->session)) {
    $session_avatar = $this->session->userdata('avatar');
    $avatar_value = is_string($session_avatar) ? trim($session_avatar) : '';
}
$profile_avatar_src = '';
if ($avatar_value !== '') {
    $avatar_scheme = parse_url($avatar_value, PHP_URL_SCHEME);
    if (is_string($avatar_scheme)) {
        if (in_array(strtolower($avatar_scheme), array('http', 'https'), TRUE)
            && filter_var($avatar_value, FILTER_VALIDATE_URL)) {
            $profile_avatar_src = $avatar_value;
        }
    } elseif (strpos($avatar_value, '//') !== 0
        && strpos($avatar_value, '\\') === FALSE
        && strpos($avatar_value, '?') === FALSE
        && strpos($avatar_value, '#') === FALSE
        && !preg_match('#(^|/)\.\.(/|$)#', $avatar_value)) {
        $profile_avatar_src = base_url(ltrim($avatar_value, '/'));
    }
}

$this->load->config('recovery_questions');
$profile_questions = $this->config->item('recovery_questions');
$profile_questions = is_array($profile_questions) ? $profile_questions : array();

$this->load->view('layouts/header', array(
    'page_title' => 'پروفایل',
    'page_styles' => array(base_url('assets/css/dashboard.css'))
));
?>

<div class="container-fluid dashboard-shell px-0">
    <div class="row g-0">
        <?php $this->load->view('layouts/app_sidebar', array('active_page' => 'profile')); ?>

        <main class="col-lg-9 col-xl-10 dashboard-main" id="profileMain" data-component="profile-page">
            <?php $this->load->view('layouts/app_header', array(
                'app_page_title' => 'پروفایل',
                'app_page_description' => 'اطلاعات حساب خود را مدیریت کنید.',
                'app_username' => $profile_username,
                'app_avatar_url' => $avatar_value
            )); ?>

            <div class="px-3 px-xl-4 py-4">
                <div class="mb-4">
                    <h2 class="h5 fw-bold mb-1">
                        <i class="fa-regular fa-id-card text-primary me-2" aria-hidden="true"></i>
                        تنظیمات پروفایل
                    </h2>
                    <p class="small text-body-secondary mb-0">اطلاعات حساب، تصویر و رمز عبور را مدیریت کنید.</p>
                </div>

                <!-- The one save button belongs to this single form, below both cards. -->
                <form id="profileForm" data-component="profile-form" enctype="multipart/form-data" novalidate>
                    <div class="d-flex flex-column gap-3">
                        <!-- Account information always appears ABOVE the password card. -->
                        <section class="card dashboard-card" aria-labelledby="profileAccountHeading" data-component="account-details">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex align-items-start gap-3 mb-4">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                                          style="width: 42px; height: 42px;" aria-hidden="true">
                                        <i class="fa-solid fa-user-gear"></i>
                                    </span>
                                    <div>
                                        <h3 class="h6 fw-bold mb-1" id="profileAccountHeading">اطلاعات حساب کاربری</h3>
                                        <p class="small text-body-secondary mb-0">اطلاعات شخصی و تنظیمات حساب شما</p>
                                    </div>
                                </div>

                                <div class="row g-4 align-items-start">
                                    <!-- Name, contact details and recovery settings. -->
                                    <div class="col-12 col-xl-9 order-2 order-xl-1">
                                        <div class="row g-3">
                                            <div class="col-12 col-md-6">
                                                <label class="form-label fw-medium" for="profileFirstName">نام <span class="text-danger" aria-hidden="true">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa-regular fa-user" aria-hidden="true"></i></span>
                                                    <input class="form-control" type="text" id="profileFirstName" name="first_name"
                                                           maxlength="100" autocomplete="given-name" required
                                                           value="<?php echo html_escape($profile_first_name); ?>">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="form-label fw-medium" for="profileLastName">نام خانوادگی <span class="text-danger" aria-hidden="true">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa-regular fa-user" aria-hidden="true"></i></span>
                                                    <input class="form-control" type="text" id="profileLastName" name="last_name"
                                                           maxlength="100" autocomplete="family-name" required
                                                           value="<?php echo html_escape($profile_last_name); ?>">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="form-label fw-medium" for="profileUsername">نام کاربری <span class="text-danger" aria-hidden="true">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa-solid fa-at" aria-hidden="true"></i></span>
                                                    <input class="form-control" type="text" id="profileUsername" name="username"
                                                           maxlength="100" autocomplete="username" required dir="ltr"
                                                           value="<?php echo html_escape($profile_username); ?>">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="form-label fw-medium" for="profileEmail">ایمیل <span class="text-danger" aria-hidden="true">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa-regular fa-envelope" aria-hidden="true"></i></span>
                                                    <input class="form-control" type="email" id="profileEmail" name="email"
                                                           maxlength="191" autocomplete="email" required dir="ltr"
                                                           value="<?php echo html_escape($profile_email); ?>">
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-medium" for="profileRecoveryQuestion">سؤال بازیابی حساب</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa-regular fa-circle-question" aria-hidden="true"></i></span>
                                                    <select class="form-select" id="profileRecoveryQuestion" name="question_id">
                                                        <option value="">بدون تغییر سؤال بازیابی</option>
                                                        <?php foreach ($profile_questions as $question_id => $question_label): ?>
                                                            <option value="<?php echo (int) $question_id; ?>"<?php echo $profile_question_id === (string) $question_id ? ' selected' : ''; ?>>
                                                                <?php echo html_escape($question_label); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-medium" for="profileRecoveryAnswer">پاسخ جدید سؤال بازیابی</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
                                                    <input class="form-control" type="password" id="profileRecoveryAnswer" name="recovery_answer"
                                                           minlength="6" maxlength="256" autocomplete="off"
                                                           placeholder="فقط برای تغییر پاسخ وارد کنید">
                                                </div>
                                                <div class="form-text">پاسخ فعلی نمایش داده نمی‌شود. برای تغییر سؤال یا پاسخ، احراز هویت با رمز فعلی لازم است.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bootstrap owns positioning; one tiny custom size is used for the avatar. -->
                                    <div class="col-12 col-xl-3 order-1 order-xl-2" data-component="avatar-picker">
                                        <div class="bg-body-tertiary rounded-3 p-3 text-center">
                                            <div class="position-relative d-inline-flex mb-3">
                                                <img id="profileAvatarImage"
                                                     class="tz-profile-avatar shadow-sm <?php echo $profile_avatar_src === '' ? 'd-none' : ''; ?>"
                                                     src="<?php echo html_escape($profile_avatar_src); ?>"
                                                     alt="تصویر پروفایل شما" width="96" height="96">
                                                <span id="profileAvatarFallback"
                                                      class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center shadow-sm <?php echo $profile_avatar_src !== '' ? 'd-none' : ''; ?>"
                                                      style="width: 96px; height: 96px;" aria-label="تصویر پیش‌فرض پروفایل">
                                                    <i class="fa-solid fa-user fa-2xl" aria-hidden="true"></i>
                                                </span>
                                            </div>
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-outline-primary btn-sm" type="button"
                                                        data-bs-toggle="modal" data-bs-target="#profileAvatarModal">
                                                    <i class="fa-solid fa-camera me-1" aria-hidden="true"></i>
                                                    تغییر عکس
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" type="button" id="profileRemoveAvatar">
                                                    <i class="fa-regular fa-trash-can me-1" aria-hidden="true"></i>
                                                    حذف عکس
                                                </button>
                                            </div>
                                            <p class="text-body-secondary small mb-0 mt-2">JPG، PNG یا WebP تا ۲ مگابایت</p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </section>

                        <!-- Password section stays BELOW account section, never side-by-side. -->
                        <section class="card dashboard-card" aria-labelledby="profilePasswordHeading" data-component="password-settings">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex align-items-start gap-3 mb-4">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                                          style="width: 42px; height: 42px;" aria-hidden="true">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>
                                    <div>
                                        <h3 class="h6 fw-bold mb-1" id="profilePasswordHeading">تغییر رمز عبور</h3>
                                        <p class="small text-body-secondary mb-0">این بخش اختیاری است؛ فقط هنگام تغییر رمز آن را تکمیل کنید.</p>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-lg-4">
                                        <label class="form-label fw-medium" for="profileCurrentPassword">رمز عبور فعلی</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                                            <input type="password" class="form-control" id="profileCurrentPassword" name="current_password"
                                                   autocomplete="current-password" placeholder="رمز فعلی خود را وارد کنید">
                                            <button type="button" class="btn btn-outline-secondary" data-profile-password-toggle="profileCurrentPassword"
                                                    aria-label="نمایش رمز عبور فعلی" aria-pressed="false">
                                                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <label class="form-label fw-medium" for="profileNewPassword">رمز عبور جدید</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-key" aria-hidden="true"></i></span>
                                            <input type="password" class="form-control" id="profileNewPassword" name="new_password"
                                                   autocomplete="new-password" minlength="8" placeholder="رمز عبور جدید">
                                            <button type="button" class="btn btn-outline-secondary" data-profile-password-toggle="profileNewPassword"
                                                    aria-label="نمایش رمز عبور جدید" aria-pressed="false">
                                                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <label class="form-label fw-medium" for="profileConfirmPassword">تکرار رمز عبور جدید</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                                            <input type="password" class="form-control" id="profileConfirmPassword" name="confirm_password"
                                                   autocomplete="new-password" minlength="8" placeholder="رمز جدید را دوباره وارد کنید">
                                            <button type="button" class="btn btn-outline-secondary" data-profile-password-toggle="profileConfirmPassword"
                                                    aria-label="نمایش تکرار رمز عبور" aria-pressed="false">
                                                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <p class="form-text mt-3 mb-0">رمز جدید باید حداقل ۸ کاراکتر و شامل حروف انگلیسی بزرگ و کوچک، عدد و یک کاراکتر ویژه باشد.</p>
                            </div>
                        </section>
                    </div>

                    <!-- Single save action: no hidden fake API request. -->
                    <div class="d-grid mt-3">
                        <button type="submit" id="profileSaveButton" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>
                            ذخیره تغییرات
                        </button>
                    </div>
                    <div id="profileMessage" class="alert alert-info d-none mt-3" role="status" aria-live="polite"></div>
                    <p class="small text-body-secondary text-center mt-2 mb-0">
                        ذخیره اطلاعات حساب، عکس و رمز عبور پس از اتصال API پروفایل فعال خواهد شد.
                    </p>
                </form>
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap photo-picker modal. Selecting a file only previews it locally. -->
<div class="modal fade tz-modal" id="profileAvatarModal" tabindex="-1" aria-labelledby="profileAvatarModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="profileAvatarModalTitle" class="modal-title h6 fw-bold mb-0">
                    <i class="fa-solid fa-camera text-primary me-2" aria-hidden="true"></i>
                    تغییر تصویر پروفایل
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <label for="profileAvatarInput" class="form-label">انتخاب تصویر جدید</label>
                <input type="file" class="form-control" id="profileAvatarInput" name="avatar" form="profileForm"
                       accept="image/jpeg,image/png,image/webp" aria-describedby="profileAvatarHelp">
                <p class="form-text mb-0 mt-2" id="profileAvatarHelp">فایل JPG، PNG یا WebP، حداکثر ۲ مگابایت. انتخاب تصویر فقط پیش‌نمایش محلی است و هنوز آپلود نمی‌شود.</p>
                <div class="alert alert-danger d-none mt-3 mb-0" id="profileAvatarError" role="alert"></div>
                <div class="alert alert-success d-none mt-3 mb-0" id="profileAvatarPreviewNotice" role="status">عکس انتخاب شد. پیش‌نمایش آن پس از بستن این پنجره دیده می‌شود؛ هنوز روی سرور ذخیره نشده است.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('layouts/logout_modal'); ?>

<?php
$this->load->view('layouts/footer', array(
    'page_scripts' => array(
        base_url('assets/js/api.js'),
        base_url('assets/js/profile-ui.js'),
        base_url('assets/js/profile.js')
    )
));
?>
