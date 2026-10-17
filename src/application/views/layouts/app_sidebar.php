<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tarazino | Shared navigation (Bootstrap 5.3 RTL + Font Awesome).
 *
 * No database calls, inline JavaScript, or DOM manipulation are used here.
 * The nav descriptor array can later become a Vue 3 component prop.
 *
 * Required from the parent view: $active_page (dashboard|transactions|categories|profile).
 * Profile route is implemented in CI3 and uses the same sidebar.
 */
$active_page = isset($active_page) && is_string($active_page) ? $active_page : 'dashboard';

$nav_items = array(
    'dashboard' => array(
        'label' => 'داشبورد',
        'icon' => 'fa-house',
        'url' => site_url('dashboard')
    ),
    'transactions' => array(
        'label' => 'تراکنش‌ها',
        'icon' => 'fa-arrow-right-arrow-left',
        'url' => site_url('transactions')
    ),
    'categories' => array(
        'label' => 'دسته‌بندی‌ها',
        'icon' => 'fa-tags',
        'url' => site_url('categories')
    ),
    'profile' => array(
        'label' => 'پروفایل',
        'icon' => 'fa-user',
        'url' => site_url('profile')
    )
);
?>

<!-- Desktop navigation; the Bootstrap grid keeps the sidebar on the right in RTL. -->
<aside class="col-lg-3 col-xl-2 d-none d-lg-block dashboard-sidebar-wrap">
    <nav class="dashboard-sidebar" aria-label="منوی اصلی ترازینو">
        <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-expanded="false" aria-label="باز کردن منو" title="باز کردن منو">
            <i class="fa-solid fa-angles-left" aria-hidden="true"></i>
        </button>
        <a class="dashboard-brand" href="<?php echo html_escape(site_url('dashboard')); ?>" aria-label="ترازینو - داشبورد">
            <!-- Brand artwork: small self-contained vector mark (calculator + three bars). -->
            <svg class="tz-brand-logo" viewBox="0 0 48 48" width="44" height="44" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="tzMarkDesktop" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#132D56"/>
                        <stop offset="1" stop-color="#174B96"/>
                    </linearGradient>
                </defs>
                <rect x="1" y="1" width="46" height="46" rx="12" fill="url(#tzMarkDesktop)"/>
                <rect x="7" y="9" width="18" height="7" rx="2.4" fill="#DAECFF"/>
                <rect x="7" y="21" width="6" height="6" rx="1.6" fill="#BBD9FF"/>
                <rect x="17" y="21" width="6" height="6" rx="1.6" fill="#BBD9FF"/>
                <rect x="7" y="31" width="6" height="6" rx="1.6" fill="#BBD9FF"/>
                <rect x="17" y="31" width="6" height="6" rx="1.6" fill="#BBD9FF"/>
                <rect x="27" y="28" width="5" height="10" rx="2" fill="#42A5F5"/>
                <rect x="34" y="21" width="5" height="17" rx="2" fill="#55C5ED"/>
                <rect x="41" y="14" width="4" height="24" rx="2" fill="#41D4B4"/>
            </svg>
            <span>ترازینو</span>
        </a>

        <div class="nav nav-pills flex-column gap-1">
            <?php foreach ($nav_items as $key => $item): ?>
                <?php $is_active = ($active_page === $key); ?>
                <a
                    class="nav-link<?php echo $is_active ? ' active' : ''; ?>"
                    href="<?php echo html_escape($item['url']); ?>"
                    aria-label="<?php echo html_escape($item['label']); ?>"
                    title="<?php echo html_escape($item['label']); ?>"
                    <?php if ($is_active): ?>aria-current="page"<?php endif; ?>
                    data-nav-key="<?php echo html_escape($key); ?>"
                >
                    <i class="fa-solid <?php echo html_escape($item['icon']); ?>" aria-hidden="true"></i>
                    <span><?php echo html_escape($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <fieldset class="tz-theme-control mt-3 pt-2 border-top border-light border-opacity-10" data-component="theme-selector">
            <legend class="tz-theme-legend"><i class="fa-solid fa-palette" aria-hidden="true"></i><span>تم برنامه</span></legend>
            <div class="tz-theme-options d-flex gap-2">
                <label class="tz-theme-option">
                    <input type="radio" name="theme-desktop" value="light" aria-label="تم روشن">
                    <i class="fa-solid fa-sun" aria-hidden="true"></i><span>روشن</span>
                </label>
                <label class="tz-theme-option">
                    <input type="radio" name="theme-desktop" value="dark" aria-label="تم تیره">
                    <i class="fa-solid fa-moon" aria-hidden="true"></i><span>تیره</span>
                </label>
            </div>
        </fieldset>

        <div class="tz-sidebar-spacer pt-3 border-top border-light border-opacity-10">
            <button
                type="button"
                class="nav-link w-100 border-0 text-start"
                aria-label="خروج"
                title="خروج"
                data-bs-toggle="modal"
                data-bs-target="#logoutConfirmModal"
            >
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                <span>خروج</span>
            </button>
        </div>
    </nav>
</aside>

<!-- Offcanvas is Bootstrap-native and only shown below the desktop breakpoint. -->
<div
    class="offcanvas offcanvas-start"
    tabindex="-1"
    id="dashboardMobileNav"
    aria-labelledby="dashboardMobileNavTitle"
>
    <div class="offcanvas-header border-bottom">
        <h2 class="offcanvas-title h5 mb-0" id="dashboardMobileNavTitle">
            <i class="fa-solid fa-calculator text-primary me-2" aria-hidden="true"></i>
            ترازینو
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="بستن منو"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column">
        <nav class="nav nav-pills flex-column gap-1 dashboard-mobile-nav" aria-label="منوی ترازینو در موبایل">
            <?php foreach ($nav_items as $key => $item): ?>
                <?php $is_active = ($active_page === $key); ?>
                <a
                    class="nav-link<?php echo $is_active ? ' active' : ''; ?>"
                    href="<?php echo html_escape($item['url']); ?>"
                    <?php if ($is_active): ?>aria-current="page"<?php endif; ?>
                    data-nav-key="<?php echo html_escape($key); ?>"
                >
                    <i class="fa-solid <?php echo html_escape($item['icon']); ?>" aria-hidden="true"></i>
                    <span><?php echo html_escape($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <fieldset class="tz-theme-control tz-mobile-theme-control mt-3 pt-2 border-top" data-component="theme-selector">
            <legend class="tz-theme-legend"><i class="fa-solid fa-palette" aria-hidden="true"></i><span>تم برنامه</span></legend>
            <div class="tz-theme-options d-flex gap-2">
                <label class="tz-theme-option">
                    <input type="radio" name="theme-mobile" value="light" aria-label="تم روشن">
                    <i class="fa-solid fa-sun" aria-hidden="true"></i><span>روشن</span>
                </label>
                <label class="tz-theme-option">
                    <input type="radio" name="theme-mobile" value="dark" aria-label="تم تیره">
                    <i class="fa-solid fa-moon" aria-hidden="true"></i><span>تیره</span>
                </label>
            </div>
        </fieldset>

        <div class="tz-sidebar-spacer pt-3 border-top">
            <!-- Existing logout.js opens the confirmation modal after offcanvas closes. -->
            <button
                type="button"
                class="nav-link w-100 d-flex align-items-center gap-2 border-0 bg-transparent text-body-secondary text-start"
                data-sidebar-logout
                data-bs-dismiss="offcanvas"
            >
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                <span>خروج</span>
            </button>
        </div>
    </div>
</div>
