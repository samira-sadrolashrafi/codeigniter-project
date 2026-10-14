<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$app_page_title = isset($app_page_title) ? $app_page_title : 'داشبورد';
$app_page_description = isset($app_page_description) ? $app_page_description : '';
$app_username = isset($app_username) && $app_username !== '' ? $app_username : 'کاربر';
?>
<header class="dashboard-header d-flex align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#dashboardMobileNav" aria-controls="dashboardMobileNav" aria-label="باز کردن منو">منو</button>
        <div>
            <h1 class="h5 mb-0"><?php echo html_escape($app_page_title); ?></h1>
            <?php if ($app_page_description !== ''): ?><p class="small text-secondary mb-0 d-none d-sm-block"><?php echo html_escape($app_page_description); ?></p><?php endif; ?>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="small text-secondary d-none d-sm-inline"><?php echo html_escape($app_username); ?></span>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">خروج</button>
    </div>
</header>
