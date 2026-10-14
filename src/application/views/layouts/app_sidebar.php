<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$active_page = isset($active_page) ? $active_page : 'dashboard';
$nav_items = array(
    'dashboard' => array('label' => 'داشبورد', 'url' => site_url('dashboard')),
    'transactions' => array('label' => 'تراکنش‌ها', 'url' => site_url('transactions')),
    'categories' => array('label' => 'دسته‌بندی‌ها', 'url' => site_url('categories'))
);
?>
<aside class="col-lg-3 col-xl-2 d-none d-lg-block dashboard-sidebar-wrap">
    <nav class="dashboard-sidebar" aria-label="ناوبری اصلی">
        <a class="dashboard-brand" href="<?php echo html_escape(site_url('dashboard')); ?>">مدیریت هزینه‌ها</a>
        <div class="nav nav-pills flex-column gap-1 mt-4">
            <?php foreach ($nav_items as $key => $item): ?>
                <a class="nav-link<?php echo $active_page === $key ? ' active' : ''; ?>"<?php echo $active_page === $key ? ' aria-current="page"' : ''; ?> href="<?php echo html_escape($item['url']); ?>"><?php echo html_escape($item['label']); ?></a>
            <?php endforeach; ?>
            <button type="button" class="nav-link text-end" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">خروج از حساب</button>
        </div>
    </nav>
</aside>

<div class="offcanvas offcanvas-start" tabindex="-1" id="dashboardMobileNav" aria-labelledby="dashboardMobileNavTitle">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5" id="dashboardMobileNavTitle">مدیریت هزینه‌ها</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="بستن"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="nav nav-pills flex-column gap-1 dashboard-mobile-nav" aria-label="ناوبری موبایل">
            <?php foreach ($nav_items as $key => $item): ?>
                <a class="nav-link<?php echo $active_page === $key ? ' active' : ''; ?>"<?php echo $active_page === $key ? ' aria-current="page"' : ''; ?> href="<?php echo html_escape($item['url']); ?>"><?php echo html_escape($item['label']); ?></a>
            <?php endforeach; ?>
            <button type="button" class="nav-link text-end" data-sidebar-logout data-bs-dismiss="offcanvas">خروج از حساب</button>
        </nav>
    </div>
</div>
