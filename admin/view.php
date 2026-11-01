<?php
require 'admin_common.php';

$view = $_GET['v'] ?? '';
if (!isset(ADMIN_VIEWS[$view])) {
    header('Location: index.php');
    exit;
}

// Фильтры для представлений: поиск по знаку, специализации или астрологу
$filters = [
    'weekly_horoscopes' => ['sign', 'Знак зодиака', 'SELECT name FROM zodiac_signs ORDER BY id'],
    'love_horoscopes' => ['sign', 'Знак зодиака', 'SELECT name FROM zodiac_signs ORDER BY id'],
    'astrologers_by_specialization' => ['specialization', 'Специализация', 'SELECT DISTINCT specialization FROM astrologers ORDER BY specialization'],
    'astrologer_reviews' => ['astrologer', 'Астролог', 'SELECT name FROM astrologers ORDER BY name'],
];
$filter = $filters[$view] ?? null;
$value = $_GET['f'] ?? '';

$sql = "SELECT * FROM $view";
$params = [];
if ($filter && $value !== '') {
    $sql .= " WHERE {$filter[0]} = ?";
    $params[] = $value;
}
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
$columns = $rows ? array_keys($rows[0]) : [];

adminHeader(ADMIN_VIEWS[$view] . " ($view)", $view);
?>
<div class="admin-toolbar">
    <span class="muted"><?= icon('eye-outline') ?> Представление доступно только для просмотра. Строк: <?= count($rows) ?></span>
    <?php if ($filter): ?>
        <form method="get" class="view-filter">
            <input type="hidden" name="v" value="<?= e($view) ?>">
            <label class="muted small"><?= e($filter[1]) ?></label>
            <select name="f" onchange="this.form.submit()">
                <option value="">Все</option>
                <?php foreach ($pdo->query($filter[2])->fetchAll(PDO::FETCH_COLUMN) as $opt): ?>
                    <option <?= $opt === $value ? 'selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>
<div class="table-wrap card">
    <table class="table admin-table">
        <thead><tr><?php foreach ($columns as $c): ?><th><?= e($c) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><?php foreach ($r as $val): ?><td class="<?= mb_strlen((string)$val) > 40 ? 'long' : '' ?>"><?= e(mb_strimwidth((string)$val, 0, 140, '…')) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td class="muted">Нет данных</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php adminFooter(); ?>
