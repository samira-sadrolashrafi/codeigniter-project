
<?php
$this->load->view('layouts/header', array(
    'page_title' => 'ورود به حساب کاربری'
));
?>

<div class="container">

    <div class="row justify-content-center align-items-center vh-100">

        <div class="col-md-5">

            <div class="card shadow border-0">

                <div class="card-body p-5">

                    <h3 class="text-center mb-3">
                        ورود به حساب کاربری
                    </h3>

                    <p class="text-center text-secondary mb-4">
                        به سامانه مدیریت هزینه‌ها خوش آمدید
                    </p>

                    <!-- General message -->
                    <div
                        id="loginMessage"
                        class="alert d-none"
                        role="alert"
                        aria-live="polite">
                    </div>

                    <form
                        id="loginForm"
                        action="<?php echo site_url('auth_api/login'); ?>"
                        method="POST"
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
                                placeholder="نام کاربری خود را وارد کنید"
                                autocomplete="username">

                            <small
                                id="username_err"
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
                                placeholder="رمز عبور خود را وارد کنید"
                                autocomplete="current-password">

                            <small
                                id="password_err"
                                class="text-danger"
                                aria-live="polite">
                            </small>

                        </div>

                        <!-- Remember Me -->
                        <div class="form-check mb-4">

                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="remember"
                                name="remember"
                                value="1">

                            <label
                                class="form-check-label"
                                for="remember">
                                مرا به خاطر بسپار
                            </label>

                        </div>

                        <!-- Login Button -->
                        <button
                            type="submit"
                            class="btn btn-primary w-100 py-2">
                            ورود
                        </button>

                    </form>

                    <div class="text-center mt-4">

                        <span class="text-secondary">
                            حساب کاربری ندارید؟
                        </span>

                        <a
                            href="<?php echo site_url('register'); ?>"
                            class="text-decoration-none">
                            ثبت‌نام کنید
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
<script src="<?php echo base_url('assets/js/auth.js'); ?>"></script>

<?php $this->load->view('layouts/footer'); ?>
