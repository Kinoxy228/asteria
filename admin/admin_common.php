<?php
// Общие настройки административной панели
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Доступ только для администратора
if (!isAdmin()) {
    flash('danger', 'Административная панель доступна только администратору.');
    header('Location: ' . url('login.php'));
    exit;
}

// Таблицы базы данных (без представлений) и их подписи
const ADMIN_TABLES = [
    'users' => 'Пользователи',
    'zodiac_signs' => 'Знаки зодиака',
    'horoscope_types' => 'Типы гороскопов',
    'horoscopes' => 'Гороскопы',
    'astrologers' => 'Астрологи',
    'consultations' => 'Консультации',
    'reviews' => 'Отзывы',
    'natal_charts' => 'Натальные карты',
    'articles' => 'Статьи',
];

// Представления (VIEW) — только просмотр
const ADMIN_VIEWS = [
    'today_horoscopes' => 'Сегодняшние гороскопы',
    'love_horoscopes' => 'Любовные гороскопы',
    'astrologers_by_specialization' => 'Астрологи по специализации',
    'weekly_horoscopes' => 'Гороскопы на неделю',
    'astrologer_reviews' => 'Отзывы об астрологах',
];

// Внешние ключи: столбец => [таблица, поле для подписи]
const FOREIGN_KEYS = [
    'sign_id' => ['zodiac_signs', 'name'],
    'sun_sign_id' => ['zodiac_signs', 'name'],
    'moon_sign_id' => ['zodiac_signs', 'name'],
    'ascendant_sign_id' => ['zodiac_signs', 'name'],
    'type_id' => ['horoscope_types', 'name'],
    'user_id' => ['users', 'name'],
    'astrologer_id' => ['astrologers', 'name'],
];

/**
 * Описание столбцов таблицы: имя, тип, обязательность, первичный ключ, варианты ENUM.
 */
function tableColumns(PDO $pdo, string $table): array
{
    $cols = [];
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        foreach ($pdo->query("SHOW COLUMNS FROM `$table`") as $c) {
            preg_match_all("/'([^']*)'/", $c['Type'], $m);
            $cols[] = [
                'name' => $c['Field'],
                'type' => strtolower(preg_replace('/ COLLATE \S+/i', '', $c['Type'])),
                'required' => $c['Null'] === 'NO' && $c['Default'] === null && $c['Extra'] !== 'auto_increment',
                'pk' => $c['Key'] === 'PRI',
                'enum' => str_starts_with(strtolower($c['Type']), 'enum') ? $m[1] : [],
            ];
        }
    } else { // SQLite — используется только при разработке
        foreach ($pdo->query("PRAGMA table_info($table)") as $c) {
            $cols[] = ['name' => $c['name'], 'type' => strtolower($c['type']), 'required' => $c['notnull'] && $c['dflt_value'] === null && !$c['pk'],
                       'pk' => (bool)$c['pk'], 'enum' => []];
        }
    }
    return $cols;
}

function fkOptions(PDO $pdo, string $col): array
{
    [$t, $label] = FOREIGN_KEYS[$col];
    return $pdo->query("SELECT id, $label AS label FROM $t ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);
}

function adminHeader(string $title, string $active = ''): void
{
    $pageTitle = $title;
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — Админ-панель Астерии</title>
    <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= url('assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="admin">
<aside class="admin-side">
    <a class="logo" href="<?= url('admin/index.php') ?>"><img src="<?= url('assets/img/logo.svg') ?>" alt="" width="34" height="34"><span>Астерия</span></a>
    <div class="side-label">Таблицы</div>
    <?php foreach (ADMIN_TABLES as $t => $name): ?>
        <a class="<?= $active === $t ? 'active' : '' ?>" href="<?= url('admin/table.php?t=' . $t) ?>"><?= icon('table') ?><?= e($name) ?></a>
    <?php endforeach; ?>
    <div class="side-label">Представления</div>
    <?php foreach (ADMIN_VIEWS as $v => $name): ?>
        <a class="<?= $active === $v ? 'active' : '' ?>" href="<?= url('admin/view.php?v=' . $v) ?>"><?= icon('eye-outline') ?><?= e($name) ?></a>
    <?php endforeach; ?>
    <div class="side-bottom">
        <a href="<?= url('index.php') ?>"><?= icon('home-outline') ?>На сайт</a>
        <a href="<?= url('logout.php') ?>"><?= icon('logout') ?>Выйти</a>
    </div>
</aside>
<main class="admin-main">
    <div class="admin-top">
        <h1><?= e($title) ?></h1>
        <span class="muted small"><?= icon('shield-account-outline') ?> <?= e($_SESSION['name']) ?></span>
    </div>
    <?= showFlash() ?>
<?php
}

function adminFooter(): void
{
    echo "</main>\n</body>\n</html>";
}
