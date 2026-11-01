<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'Астерия';
$current = basename($_SERVER['SCRIPT_NAME']);
$nav = [
    'index.php' => 'Главная',
    'horoscopes.php' => 'Гороскопы',
    'personal.php' => 'Персональный гороскоп',
    'astrologers.php' => 'Астрологи',
    'articles.php' => 'Статьи',
];
$activeMap = ['astrologer.php' => 'astrologers.php', 'article.php' => 'articles.php'];
$activePage = $activeMap[$current] ?? $current;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> — Астерия</title>
    <meta name="description" content="Астерия — астрологический портал: ежедневные гороскопы, персональная натальная карта и консультации астрологов.">
    <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= url('assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<div class="sky" aria-hidden="true"></div>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="<?= url('index.php') ?>">
            <img src="<?= url('assets/img/logo.svg') ?>" alt="" width="40" height="40">
            <span>Астерия</span>
        </a>
        <button class="nav-toggle" type="button" aria-label="Меню" onclick="document.body.classList.toggle('nav-open')"><?= icon('menu') ?></button>
        <nav class="main-nav">
            <?php foreach ($nav as $file => $title): ?>
                <a href="<?= url($file) ?>" class="<?= $activePage === $file ? 'active' : '' ?>"><?= e($title) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="header-actions">
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= url('admin/index.php') ?>"><?= icon('cog-outline') ?>Админ-панель</a>
                <?php endif; ?>
                <a class="btn btn-ghost btn-sm" href="<?= url('account.php') ?>"><?= icon('account-circle-outline') ?><?= e(explode(' ', $_SESSION['name'])[0]) ?></a>
                <a class="icon-btn" href="<?= url('logout.php') ?>" title="Выйти"><?= icon('logout') ?></a>
            <?php else: ?>
                <a class="btn btn-ghost btn-sm" href="<?= url('login.php') ?>">Войти</a>
                <a class="btn btn-gold btn-sm" href="<?= url('register.php') ?>">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="site-main">
<?php $flashHtml = showFlash(); if ($flashHtml): ?>
    <div class="container flash-wrap"><?= $flashHtml ?></div>
<?php endif; ?>
