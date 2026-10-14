<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$page_title = isset($page_title) ? $page_title : 'مدیریت هزینه‌ها';
$show_navbar = isset($show_navbar) ? $show_navbar : FALSE;
$page_styles = isset($page_styles) && is_array($page_styles) ? $page_styles : array();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="پنل مدیریت هزینه‌ها و تراکنش‌های شخصی">
    <meta name="theme-color" content="#f8f9fa">
    <title><?php echo html_escape($page_title); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        :root { --bs-body-font-family: 'Vazirmatn', sans-serif; }
    </style>
    <?php foreach ($page_styles as $style): ?>
        <link rel="stylesheet" href="<?php echo html_escape($style); ?>">
    <?php endforeach; ?>
</head>
<body class="bg-light">
<?php if ($show_navbar): ?>
    <?php $this->load->view('layouts/navbar', array(
        'username' => isset($username) ? $username : ''
    )); ?>
<?php endif; ?>
