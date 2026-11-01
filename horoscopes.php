<?php
require 'includes/db.php';
require_once 'includes/functions.php';

$signs = allSigns($pdo);
$types = $pdo->query('SELECT * FROM horoscope_types ORDER BY id')->fetchAll();

$signSlug = $_GET['sign'] ?? '';
$typeSlug = $_GET['type'] ?? 'general';
$period = $_GET['period'] ?? 'today';

$periods = ['yesterday' => 'Вчера', 'today' => 'Сегодня', 'tomorrow' => 'Завтра', 'week' => 'Неделя'];
if (!isset($periods[$period])) $period = 'today';

$sign = null;
foreach ($signs as $s) if ($s['slug'] === $signSlug) $sign = $s;
$type = $types[0];
foreach ($types as $t) if ($t['slug'] === $typeSlug) $type = $t;

$offsets = ['yesterday' => -1, 'today' => 0, 'tomorrow' => 1];
$date = $period === 'week' ? date('Y-m-d') : date('Y-m-d', strtotime(($offsets[$period] >= 0 ? '+' : '') . $offsets[$period] . ' day'));

// Условие по периоду: день — точная дата, неделя — дата попадает в интервал
$periodSql = $period === 'week'
    ? "h.period = 'week' AND ? BETWEEN h.date_from AND h.date_to"
    : "h.period = 'day' AND h.date_from = ?";

if ($sign) {
    // Все типы гороскопа для выбранного знака
    $stmt = $pdo->prepare("SELECT h.*, t.name AS type_name, t.slug AS type_slug
        FROM horoscopes h JOIN horoscope_types t ON t.id = h.type_id
        WHERE $periodSql AND h.sign_id = ? ORDER BY t.id");
    $stmt->execute([$date, $sign['id']]);
    $rows = $stmt->fetchAll();
    $main = null;
    foreach ($rows as $r) if ($r['type_slug'] === $type['slug']) $main = $r;
} else {
    // Гороскопы выбранного типа для всех знаков
    $stmt = $pdo->prepare("SELECT h.*, s.name, s.slug, s.date_start, s.date_end, s.element
        FROM horoscopes h JOIN zodiac_signs s ON s.id = h.sign_id
        WHERE $periodSql AND h.type_id = ? ORDER BY s.id");
    $stmt->execute([$date, $type['id']]);
    $rows = $stmt->fetchAll();
}

function link_with(array $change): string
{
    $params = array_merge(['sign' => $_GET['sign'] ?? '', 'type' => $_GET['type'] ?? 'general', 'period' => $_GET['period'] ?? 'today'], $change);
    $params = array_filter($params, fn($v) => $v !== '');
    return url('horoscopes.php?' . http_build_query($params));
}

$periodTitle = $period === 'week' && $rows ? 'на неделю ' . ruDate($rows[0]['date_from'], false) . ' – ' . ruDate($rows[0]['date_to'])
    : 'на ' . ruDate($date);
$pageTitle = $sign ? 'Гороскоп для знака ' . $sign['name'] : 'Ежедневные гороскопы';
require 'includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <nav class="crumbs"><a href="<?= url('index.php') ?>">Главная</a> / <?php if ($sign): ?><a href="<?= link_with(['sign' => '']) ?>">Гороскопы</a> / <span><?= e($sign['name']) ?></span><?php else: ?><span>Гороскопы</span><?php endif; ?></nav>
        <h1><?= $sign ? e($sign['name']) . ': ' . mb_strtolower(e($type['name'])) . ' гороскоп' : 'Ежедневные гороскопы' ?></h1>
        <p class="lead"><?= $sign ? 'Прогноз ' . $periodTitle : 'Выберите знак зодиака, тип гороскопа и период.' ?></p>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="sign-picker">
            <a class="<?= !$sign ? 'active' : '' ?>" href="<?= link_with(['sign' => '']) ?>"><?= icon('star-four-points') ?><span>Все знаки</span></a>
            <?php foreach ($signs as $s): ?>
                <a class="<?= $sign && $sign['id'] === $s['id'] ? 'active' : '' ?>" href="<?= link_with(['sign' => $s['slug']]) ?>"><?= zodiacIcon($s['slug']) ?><span><?= e($s['name']) ?></span></a>
            <?php endforeach; ?>
        </div>

        <div class="filters-bar">
            <div class="pills">
                <?php foreach ($types as $t): ?>
                    <a class="pill <?= $t['slug'] === $type['slug'] ? 'active' : '' ?>" href="<?= link_with(['type' => $t['slug']]) ?>"><?= icon(typeIcon($t['slug'])) ?><?= e($t['name']) ?></a>
                <?php endforeach; ?>
            </div>
            <div class="pills pills-period">
                <?php foreach ($periods as $key => $title): ?>
                    <a class="pill <?= $key === $period ? 'active' : '' ?>" href="<?= link_with(['period' => $key]) ?>"><?= $title ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($sign): ?>
            <div class="sign-layout">
                <article class="horo-main">
                    <div class="horo-head">
                        <span class="sign-glyph big"><?= zodiacIcon($sign['slug']) ?></span>
                        <div>
                            <div class="muted small"><?= e(signPeriod($sign)) ?></div>
                            <h2><?= e($sign['name']) ?> — <?= mb_strtolower(e($type['name'])) ?></h2>
                            <div class="muted small"><?= ucfirst($periodTitle) ?></div>
                        </div>
                    </div>
                    <?php if ($main): ?>
                        <p class="horo-text"><?= e($main['content']) ?></p>
                        <div class="horo-rating">Благоприятность: <?= stars((int)$main['rating']) ?></div>
                    <?php else: ?>
                        <p class="muted">Гороскоп на этот период ещё не опубликован.</p>
                    <?php endif; ?>
                    <?php if (count($rows) > 1): ?>
                        <h3 class="mt">Другие сферы</h3>
                        <div class="other-types">
                            <?php foreach ($rows as $r): if ($r['type_slug'] === $type['slug']) continue; ?>
                                <a class="other-type" href="<?= link_with(['type' => $r['type_slug']]) ?>">
                                    <span class="type-icon sm"><?= icon(typeIcon($r['type_slug'])) ?></span>
                                    <div><strong><?= e($r['type_name']) ?></strong><p class="muted small"><?= e(mb_strimwidth($r['content'], 0, 90, '…')) ?></p></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
                <aside class="sign-info">
                    <h3>О знаке</h3>
                    <dl>
                        <dt>Стихия</dt><dd><?= icon(elementIcon($sign['element'])) ?> <?= e($sign['element']) ?></dd>
                        <dt>Управитель</dt><dd><?= icon('orbit') ?> <?= e($sign['planet']) ?></dd>
                        <dt>Даты</dt><dd><?= icon('calendar-today') ?> <?= e(signPeriod($sign)) ?></dd>
                    </dl>
                    <p class="muted"><?= e($sign['description']) ?></p>
                    <a class="btn btn-outline btn-block" href="<?= url('personal.php') ?>">Персональный гороскоп</a>
                </aside>
            </div>
        <?php else: ?>
            <h2 class="list-title"><?= e($type['name']) ?> гороскоп <?= $periodTitle ?></h2>
            <div class="horo-list">
                <?php foreach ($rows as $r): ?>
                    <article class="horo-item">
                        <a class="sign-glyph" href="<?= link_with(['sign' => $r['slug']]) ?>"><?= zodiacIcon($r['slug']) ?></a>
                        <div>
                            <div class="horo-item-head">
                                <h3><a href="<?= link_with(['sign' => $r['slug']]) ?>"><?= e($r['name']) ?></a></h3>
                                <span class="muted small"><?= e(signPeriod($r)) ?></span>
                                <?= stars((int)$r['rating']) ?>
                            </div>
                            <p><?= e($r['content']) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$rows): ?><p class="muted">Гороскопы на этот период ещё не опубликованы.</p><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
