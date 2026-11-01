<?php
require 'admin_common.php';

$counts = [];
foreach (array_keys(ADMIN_TABLES) as $t) {
    $counts[$t] = (int)$pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
}
$vcounts = [];
foreach (array_keys(ADMIN_VIEWS) as $v) {
    $vcounts[$v] = (int)$pdo->query("SELECT COUNT(*) FROM $v")->fetchColumn();
}
$pendingReviews = (int)$pdo->query('SELECT COUNT(*) FROM reviews WHERE is_approved = 0')->fetchColumn();
$newConsult = (int)$pdo->query("SELECT COUNT(*) FROM consultations WHERE status = 'Новая'")->fetchColumn();

adminHeader('Панель администратора');
?>
<div class="stat-row">
    <div class="stat"><span><?= $counts['users'] ?></span>пользователей</div>
    <div class="stat"><span><?= $counts['horoscopes'] ?></span>гороскопов</div>
    <a class="stat warn" href="table.php?t=reviews"><span><?= $pendingReviews ?></span>отзывов на модерации</a>
    <a class="stat warn" href="table.php?t=consultations"><span><?= $newConsult ?></span>новых заявок на консультацию</a>
</div>

<h2 class="admin-h2">Таблицы базы данных</h2>
<div class="tile-grid">
    <?php foreach (ADMIN_TABLES as $t => $name): ?>
        <a class="tile" href="table.php?t=<?= $t ?>">
            <?= icon('table', 'ico tile-ico') ?>
            <div><strong><?= e($name) ?></strong><div class="muted small"><?= $t ?> · записей: <?= $counts[$t] ?></div></div>
        </a>
    <?php endforeach; ?>
</div>

<h2 class="admin-h2">Представления (VIEW)</h2>
<div class="tile-grid">
    <?php foreach (ADMIN_VIEWS as $v => $name): ?>
        <a class="tile tile-view" href="view.php?v=<?= $v ?>">
            <?= icon('eye-outline', 'ico tile-ico') ?>
            <div><strong><?= e($name) ?></strong><div class="muted small"><?= $v ?> · строк: <?= $vcounts[$v] ?></div></div>
        </a>
    <?php endforeach; ?>
</div>
<?php adminFooter(); ?>
