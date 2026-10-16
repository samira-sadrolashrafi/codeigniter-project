<?php
 defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tarazino | Password recovery modal (CodeIgniter 3).
 *
 * Included once by auth/login.php and controlled by assets/js/recovery.js.
 * The three existing steps and all their IDs, name attributes and API URLs
 * are deliberately preserved. No inline JS, no jQuery, no new backend fields.
 *
 * Workflow: username -> security answer -> new password.
 * This is NOT the removed recovery-question enrollment modal.
 *
 * Bootstrap 5.3 RTL owns layout, state utilities, forms and modals;
 * Font Awesome is loaded centrally by layouts/header.php.
 */
?>
<div
    class="modal fade tz-modal"
    id="recoveryModal"
    tabindex="-1"
    aria-labelledby="recoveryModalTitle"
    aria-describedby="recoveryModalDescription"
    aria-hidden="true"
    data-question-url="<?php echo html_escape(site_url('auth/recovery/question')); ?>"
    data-verify-url="<?php echo html_escape(site_url('auth/recovery/verify')); ?>"
    data-reset-url="<?php echo html_escape(site_url('auth/recovery/reset')); ?>"
>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header p-3 p-sm-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center rounded-3 p-3" aria-hidden="true">
                        <i class="fa-solid fa-key fs-5"></i>
                    </span>
                    <div>
                        <h2 class="modal-title h5 fw-bold mb-1" id="recoveryModalTitle">فراموشی رمز عبور</h2>
                        <p class="text-body-secondary small mb-0" id="recoveryModalDescription">
                            بازیابی حساب با سؤال امنیتی
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="بستن پنجره"></button>
            </div>

            <div class="modal-body p-3 p-sm-4">
                <div
                    class="alert d-none"
                    id="recoveryMessage"
                    role="alert"
                    aria-live="polite"
                    aria-atomic="true"
                ></div>

                <!-- Step 1: The login name identifies the account's recovery question. -->
                <form id="recoveryQuestionForm" novalidate>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">مرحله ۱ از ۳</span>
                        <span class="fw-semibold">شناسایی حساب</span>
                    </div>
                    <p class="text-body-secondary small mb-4">
                        نام کاربری حساب خودت رو وارد کن تا سؤال امنیتی‌ای که موقع ثبت‌نام انتخاب کردی نمایش داده بشه.
                    </p>
                    <div class="mb-4">
                        <label for="recoveryUsername" class="form-label fw-semibold">
                            نام کاربری <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input
                                type="text"
                                class="form-control"
                                name="username"
                                id="recoveryUsername"
                                minlength="3"
                                maxlength="100"
                                autocomplete="username"
                                autocapitalize="none"
                                spellcheck="false"
                                dir="ltr"
                                placeholder="username"
                                required
                            >
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>
                        نمایش سؤال امنیتی
                    </button>
                </form>

                <!-- Step 2: JS fills the question safely using textContent. -->
                <form id="recoveryVerifyForm" class="d-none" novalidate>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">مرحله ۲ از ۳</span>
                        <span class="fw-semibold">تأیید هویت</span>
                    </div>
                    <div class="rounded-3 bg-body-tertiary border p-3 mb-4">
                        <div class="d-flex align-items-center gap-2 text-body-secondary small mb-2">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                            سؤال امنیتی انتخاب‌شده
                        </div>
                        <p id="recoveryQuestionLabel" class="fw-semibold mb-0" aria-live="polite"></p>
                    </div>
                    <div class="mb-4">
                        <label for="recoveryAnswer" class="form-label fw-semibold">
                            پاسخ محرمانه <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input
                                type="password"
                                class="form-control"
                                name="answer"
                                id="recoveryAnswer"
                                maxlength="256"
                                autocomplete="off"
                                aria-describedby="recoveryAnswerHelp"
                                required
                            >
                        </div>
                        <div id="recoveryAnswerHelp" class="form-text">
                            پاسخی رو وارد کن که زمان ثبت‌نام ذخیره کردی.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="fa-solid fa-check me-2" aria-hidden="true"></i>
                        بررسی پاسخ
                    </button>
                </form>

                <!-- Step 3: The server-issued nonce is used only to reset a password. -->
                <form id="recoveryResetForm" class="d-none" novalidate>
                    <input type="hidden" name="nonce" id="recoveryNonce">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">مرحله ۳ از ۳</span>
                        <span class="fw-semibold">تعیین رمز جدید</span>
                    </div>
                    <p class="text-body-secondary small mb-4">
                        پاسخ امنیتی تأیید شد. حالا یک رمز عبور جدید برای حسابت انتخاب کن.
                    </p>
                    <div class="mb-3">
                        <label for="recoveryNewPassword" class="form-label fw-semibold">
                            رمز عبور جدید <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input
                                type="password"
                                class="form-control"
                                name="password"
                                id="recoveryNewPassword"
                                minlength="8"
                                maxlength="1024"
                                autocomplete="new-password"
                                dir="ltr"
                                aria-describedby="recoveryPasswordHelp recovery_password_err"
                                required
                            >
                        </div>
                        <div id="recoveryPasswordHelp" class="form-text">
                            حداقل ۸ کاراکتر؛ شامل حروف بزرگ و کوچک انگلیسی، عدد و یک علامت مثل @ یا #.
                        </div>
                        <div id="recovery_password_err" class="text-danger small mt-1" aria-live="polite"></div>
                    </div>
                    <div class="mb-4">
                        <label for="recoveryConfirmPassword" class="form-label fw-semibold">
                            تکرار رمز عبور جدید <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input
                                type="password"
                                class="form-control"
                                name="password_confirm"
                                id="recoveryConfirmPassword"
                                maxlength="1024"
                                autocomplete="new-password"
                                dir="ltr"
                                aria-describedby="recovery_confirm_err"
                                required
                            >
                        </div>
                        <div id="recovery_confirm_err" class="text-danger small mt-1" aria-live="polite"></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>
                        ذخیره رمز جدید
                    </button>
                </form>
            </div>

            <div class="modal-footer justify-content-between gap-2 p-3">
                <button type="button" class="btn btn-link text-decoration-none d-none" id="recoveryStartOver">
                    <i class="fa-solid fa-rotate-left me-1" aria-hidden="true"></i>
                    شروع دوباره
                </button>
                <button type="button" class="btn btn-outline-secondary ms-auto" data-bs-dismiss="modal">
                    بستن
                </button>
            </div>
        </div>
    </div>
</div>
