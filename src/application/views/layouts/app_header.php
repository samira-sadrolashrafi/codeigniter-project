<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tarazino | Application header (CodeIgniter 3 / Bootstrap 5 RTL).
 *
 * Presentation-only view: no database queries or profile upload logic here.
 * Future Vue 3 AppHeader props: title, description, username, avatarUrl.
 *
 * Optional inputs from the page/controller:
 *   $app_page_title, $app_page_description, $app_username,
 *   $app_avatar_url: full http(s) URL OR path relative to the site's base URL,
 *                    e.g. 'uploads/avatars/photo.webp'.
 *
 * As a temporary fallback, a nonempty session 'avatar' value is supported.
 * If no avatar is available, Font Awesome's user icon is shown.
 */
$app_page_title = isset($app_page_title) && is_string($app_page_title) && trim($app_page_title) !== ''
    ? trim($app_page_title)
    : 'داشبورد';
$app_page_description = isset($app_page_description) && is_string($app_page_description)
    ? trim($app_page_description)
    : '';
$app_username = isset($app_username) && is_string($app_username) && trim($app_username) !== ''
    ? trim($app_username)
    : 'کاربر';

// Explicit view data wins over any session value, avoiding stale profile pictures.
$app_avatar_value = isset($app_avatar_url) && is_string($app_avatar_url)
    ? trim($app_avatar_url)
    : '';

if ($app_avatar_value === '' && isset($this->session)) {
    $session_avatar = $this->session->userdata('avatar');
    if (is_string($session_avatar)) {
        $app_avatar_value = trim($session_avatar);
    }
}

// Accept only web image URLs or site-relative file paths; never data:/javascript: URIs.
$app_avatar_src = '';
if ($app_avatar_value !== '') {
    $avatar_scheme = parse_url($app_avatar_value, PHP_URL_SCHEME);
    if (is_string($avatar_scheme)) {
        if (in_array(strtolower($avatar_scheme), array('http', 'https'), TRUE)
            && filter_var($app_avatar_value, FILTER_VALIDATE_URL)) {
            $app_avatar_src = $app_avatar_value;
        }
    } elseif (strpos($app_avatar_value, '//') !== 0
        && strpos($app_avatar_value, '\\') === FALSE
        && strpos($app_avatar_value, '?') === FALSE
        && strpos($app_avatar_value, '#') === FALSE
        && !preg_match('#(^|/)\.\.(/|$)#', $app_avatar_value)) {
        // Paths stored in users.avatar can be rendered without exposing filesystem paths.
        $app_avatar_src = base_url(ltrim($app_avatar_value, '/'));
    }
}
?>

<header class="dashboard-header bg-body d-flex align-items-center justify-content-between flex-wrap gap-3 px-3 px-xl-4 py-3">
    <div class="d-flex align-items-center gap-3 flex-grow-1">
        <button
            type="button"
            class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center d-lg-none"
            data-bs-toggle="offcanvas"
            data-bs-target="#dashboardMobileNav"
            aria-controls="dashboardMobileNav"
            aria-label="باز کردن منوی اصلی"
        >
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>

        <div class="min-w-0">
            <h1 class="h5 fw-bold mb-1"><?php echo html_escape($app_page_title); ?></h1>
            <?php if ($app_page_description !== ''): ?>
                <p class="text-body-secondary small mb-0 d-none d-sm-block">
                    <?php echo html_escape($app_page_description); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile menu: the same avatar URL can later be passed as a Vue prop. -->
    <div class="dropdown flex-shrink-0">
        <button
            type="button"
            class="btn btn-light border d-flex align-items-center gap-2 px-2 px-sm-3 py-2 text-body"
            id="tarazinoAccountMenu"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="باز کردن منوی حساب کاربری"
        >
            <?php if ($app_avatar_src !== ''): ?>
                <img
                    src="<?php echo html_escape($app_avatar_src); ?>"
                    width="40"
                    height="40"
                    class="rounded-circle object-fit-cover flex-shrink-0"
                    alt="عکس پروفایل <?php echo html_escape($app_username); ?>"
                    loading="eager"
                    decoding="async"
                >
            <?php else: ?>
                <span
                    class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle flex-shrink-0"
                    style="width: 40px; height: 40px;"
                    aria-hidden="true"
                >
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                </span>
            <?php endif; ?>

            <span class="d-none d-sm-flex flex-column align-items-start text-start lh-sm">
                <span class="fw-semibold small text-truncate" style="max-width: 11rem;">
                    <?php echo html_escape($app_username); ?>
                </span>
                <span class="text-body-secondary small mt-1">حساب کاربری</span>
            </span>
            <i class="fa-solid fa-chevron-down text-body-secondary small ms-1" aria-hidden="true"></i>
        </button>

        <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-2" aria-labelledby="tarazinoAccountMenu">
            <li>
                <h2 class="dropdown-header small mb-0"><?php echo html_escape($app_username); ?></h2>
            </li>
            <li>
                <a
                    class="dropdown-item d-flex align-items-center gap-2"
                    href="<?php echo html_escape(site_url('profile')); ?>"
                >
                    <i class="fa-solid fa-user-gear text-body-secondary" aria-hidden="true"></i>
                    <span>پروفایل</span>
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <button
                    type="button"
                    class="dropdown-item d-flex align-items-center gap-2 text-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#logoutConfirmModal"
                >
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                    <span>خروج از حساب</span>
                </button>
            </li>
        </ul>
    </div>
</header>


