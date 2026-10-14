<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$username = isset($username) && $username !== '' ? $username : 'کاربر';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark" aria-label="ناوبری اصلی">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?php echo site_url('dashboard'); ?>">
            مدیریت هزینه‌ها
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="نمایش منو">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo site_url('dashboard'); ?>">داشبورد</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo site_url('categories'); ?>">دسته‌بندی‌ها</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo site_url('transactions'); ?>">تراکنش‌ها</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <span class="navbar-text text-white">
                    <?php echo html_escape($username); ?>
                </span>
                <button type="button" id="logoutButton" class="btn btn-outline-light btn-sm"
                        data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
                    خروج
                </button>
            </div>
        </div>
    </div>
</nav>
<?php $this->load->view('layouts/logout_modal'); ?>
