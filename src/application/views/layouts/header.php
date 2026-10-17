<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tarazino | Shared document header (CodeIgniter 3).
 *
 * Keeps the original view inputs intact:
 *   $page_title  : string
 *   $page_styles : array of stylesheet URLs (e.g. dashboard.css)
 *   $show_navbar : bool (for legacy pages that still load layouts/navbar)
 *   $username    : optional user name for the legacy navbar
 *
 * Bootstrap 5.3 RTL owns layout, components and color modes.
 * Font Awesome is loaded as CSS/webfonts (no SVG replacement JavaScript).
 * The small theme bootstrap is independent from business logic and can later
 * live in the Vue 3 application entry point without changing the HTML/CSS.
 */
$page_title = isset($page_title) && is_string($page_title) && trim($page_title) !== ''
    ? trim($page_title)
    : 'ترازینو';
$document_title = (strpos($page_title, 'ترازینو') !== FALSE)
    ? $page_title
    : $page_title . ' | ترازینو';

$show_navbar = isset($show_navbar) && $show_navbar === TRUE;
$page_styles = isset($page_styles) && is_array($page_styles) ? $page_styles : array();

// Local vendor assets are preferred. Until installed, use the pinned CDN URL.
// If you copy Font Awesome locally, keep its /webfonts directory alongside /css.
$fontawesome_path = 'assets/vendor/fontawesome/css/all.min.css';
$fontawesome_url = is_file(FCPATH . $fontawesome_path)
    ? base_url($fontawesome_path)
    : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css';
?>
<!doctype html>
<html lang="fa" dir="rtl" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ترازینو، سامانه مدیریت مالی شخصی، درآمدها، هزینه‌ها و موجودی">
    <meta name="theme-color" content="#0f213a">
    <title><?php echo html_escape($document_title); ?></title>

    <!-- Apply the saved Bootstrap color mode before styles load, avoiding a flash. -->
    <script>
        (function() {
            'use strict';
            try {
                var savedTheme = window.localStorage.getItem('tarazino-theme');
                if (savedTheme === 'light' || savedTheme === 'dark') {
                    document.documentElement.setAttribute('data-bs-theme', savedTheme);
                }
                document.documentElement.setAttribute(
                    'data-sidebar-expanded',
                    window.localStorage.getItem('tarazino-sidebar-expanded') === 'true' ? 'true' : 'false'
                );
            } catch (error) {
                // Storage may be blocked; both themes fall back to their defaults.
                document.documentElement.setAttribute('data-sidebar-expanded', 'false');
            }
        }());
    </script>

    <!-- Persian numbers and Jalali date formatting -->
    <script src="<?= base_url('assets/js/persian-utils.js?v=' . filemtime(FCPATH . 'assets/js/persian-utils.js')) ?>"></script>

    <!-- Persian typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap">

    <!-- Bootstrap 5.3 RTL: keep this version in sync with layouts/footer.php. -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css"
        crossorigin="anonymous">

    <!-- Font Awesome Free: CSS + webfonts, compatible with <i class="fa-solid ...">. -->
    <link rel="stylesheet" href="<?php echo html_escape($fontawesome_url); ?>">

    <style>
        :root,
        [data-bs-theme="light"],
        [data-bs-theme="dark"] {
            --bs-body-font-family: 'Vazirmatn', system-ui, sans-serif;
        }
    </style>

    <!-- Jalali Datepicker CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/jalalidatepicker/jalalidatepicker.min.css'); ?>">
    
    <!-- Page-specific CSS is passed from the calling view; no duplicate imports. -->
    <?php foreach ($page_styles as $style): ?>
        <?php if (is_string($style) && trim($style) !== ''): ?>
            <link rel="stylesheet" href="<?php echo html_escape($style); ?>">
        <?php endif; ?>
    <?php endforeach; ?>
</head>

<body class="bg-body">
    <?php if ($show_navbar): ?>
        <?php $this->load->view('layouts/navbar', array(
            'username' => isset($username) && is_string($username) ? $username : ''
        )); ?>
    <?php endif; ?>
