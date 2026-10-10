
<?php
$this->load->view('layouts/header', array(
    'page_title' => 'ثبت‌نام'
));
?>

<div class="container">

    <div class="row justify-content-center align-items-center vh-100">

        <div class="col-md-5">

            <div class="card shadow border-0">

                <div class="card-body p-5">

                    <h3 class="text-center mb-3">
                        ایجاد حساب کاربری
                    </h3>

                    <p class="text-center text-secondary mb-4">
                        برای مدیریت هزینه‌های خود ثبت‌نام کنید
                    </p>

                    <!-- General message -->
                    <div
                        id="registerMessage"
                        class="alert d-none"
                        role="alert"
                        aria-live="polite">
                    </div>

                    <form
                        id="registerForm"
                        action="<?php echo site_url('auth_api/register'); ?>"
                        method="POST"
                        data-success-url="<?php echo site_url('login'); ?>"
                        novalidate>

                        <!-- Username -->
                        <div class="mb-3">

                            <label for="username" class="form-label">
                                نام کاربری
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="username"
                                name="username"
                                placeholder="نام کاربری"
                                autocomplete="username">

                            <small
                                id="username_err"
                                class="text-danger"
                                aria-live="polite">
                            </small>

                        </div>

                        <!-- Email -->
                        <div class="mb-3">

                            <label for="email" class="form-label">
                                ایمیل
                            </label>

                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="example@gmail.com"
                                autocomplete="email">

                            <small
                                id="email_err"
                                class="text-danger"
                                aria-live="polite">
                            </small>

                        </div>

                        <!-- Password -->
                        <div class="mb-3">

                            <label for="password" class="form-label">
                                رمز عبور
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="رمز عبور"
                                autocomplete="new-password"
                                aria-describedby="passwordHelp">

                            <small
                                id="password_err"
                                class="text-danger"
                                aria-live="polite">
                            </small>

                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">

                            <label for="password_confirm" class="form-label">
                                تکرار رمز عبور
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="password_confirm"
                                name="password_confirm"
                                placeholder="رمز عبور را دوباره وارد کنید"
                                autocomplete="new-password">

                            <small
                                id="confirm_password_err"
                                class="text-danger"
                                aria-live="polite">
                            </small>

                        </div>

                        <!-- Register Button -->
                        <button
                            type="submit"
                            class="btn btn-primary w-100 py-2">
                            ثبت‌نام
                        </button>

                    </form>

                    <div class="text-center mt-4">

                        <span class="text-secondary">
                            قبلاً ثبت‌نام کرده‌اید؟
                        </span>

                        <a
                            href="<?php echo site_url('login'); ?>"
                            class="text-decoration-none">
                            وارد شوید
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="<?php echo base_url('assets/js/auth.js'); ?>"></script>

<?php $this->load->view('layouts/footer'); ?>
