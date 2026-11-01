<?php
require 'includes/db.php';
require_once 'includes/functions.php';

$today = date('Y-m-d');
// Гороскоп на сегодня для всех знаков зодиака (общий тип)
$stmt = $pdo->prepare("SELECT h.*, s.name, s.slug, s.element, s.date_start, s.date_end
    FROM horoscopes h
    JOIN zodiac_signs s ON s.id = h.sign_id
    JOIN horoscope_types t ON t.id = h.type_id
    WHERE h.period = 'day' AND t.slug = 'general' AND h.date_from = ?
    ORDER BY s.id");
$stmt->execute([$today]);
$todayList = $stmt->fetchAll();

$types = $pdo->query("SELECT * FROM horoscope_types WHERE slug <> 'general' ORDER BY id")->fetchAll();
$astrologers = $pdo->query('SELECT * FROM astrologers ORDER BY rating DESC, experience DESC LIMIT 3')->fetchAll();
$articles = $pdo->query('SELECT * FROM articles ORDER BY published_at DESC LIMIT 3')->fetchAll();
$moon = moonPhase();

$mySign = null;
if (isLoggedIn()) {
    $u = $pdo->prepare('SELECT birth_date FROM users WHERE id = ?');
    $u->execute([$_SESSION['user_id']]);
    $bd = $u->fetchColumn();
    if ($bd) $mySign = signByDate($pdo, $bd);
}

$pageTitle = 'Гороскоп на сегодня';
require 'includes/header.php';
?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-text">
            <span class="eyebrow"><?= icon('calendar-today') ?><?= ruDate($today) ?>, <?= weekdayRu($today) ?></span>
            <h1>Гороскоп на сегодня<br><span class="gold">для всех знаков зодиака</span></h1>
            <p class="lead">Ежедневные прогнозы по любви, карьере, здоровью и финансам. Узнайте, что звёзды готовят вам сегодня, и постройте свою натальную карту.</p>
            <div class="hero-actions">
                <a class="btn btn-gold" href="#signs"><?= icon('star-four-points') ?>Выбрать знак</a>
                <a class="btn btn-outline" href="<?= url('personal.php') ?>"><?= icon('chart-donut') ?>Натальная карта</a>
            </div>
        </div>
        <aside class="moon-card">
            <?php $shift = $moon['phase'] < 0.5 ? -$moon['phase'] * 200 : 200 - $moon['phase'] * 200; ?>
            <div class="moon-visual" style="--shift: <?= round($shift, 1) ?>%"><span></span></div>
            <div>
                <div class="muted small">Луна сегодня</div>
                <div class="moon-name"><?= e($moon['name']) ?></div>
                <div class="muted small"><?= $moon['day'] ?>-й лунный день</div>
            </div>
            <?php if ($mySign): ?>
                <a class="my-sign" href="<?= url('horoscopes.php?sign=' . $mySign['slug']) ?>">
                    <?= zodiacIcon($mySign['slug']) ?> Ваш знак — <?= e($mySign['name']) ?> <?= icon('chevron-right') ?>
                </a>
            <?php endif; ?>
        </aside>
    </div>
</section>

<section class="section" id="signs">
    <div class="container">
        <div class="section-head">
            <h2>Гороскоп на <?= ruDate($today, false) ?></h2>
            <a class="link" href="<?= url('horoscopes.php') ?>">Все гороскопы <?= icon('arrow-right') ?></a>
        </div>
        <div class="sign-grid">
            <?php foreach ($todayList as $h): ?>
                <a class="sign-card el-<?= e(['Огонь' => 'fire', 'Земля' => 'earth', 'Воздух' => 'air', 'Вода' => 'water'][$h['element']]) ?>" href="<?= url('horoscopes.php?sign=' . $h['slug']) ?>">
                    <div class="sign-top">
                        <span class="sign-glyph"><?= zodiacIcon($h['slug']) ?></span>
                        <div>
                            <h3><?= e($h['name']) ?></h3>
                            <span class="muted small"><?= e(signPeriod($h)) ?></span>
                        </div>
                    </div>
                    <p><?= e(mb_strimwidth($h['content'], 0, 120, '…')) ?></p>
                    <span class="sign-more">Читать <?= icon('chevron-right') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head"><h2>Гороскопы по темам</h2></div>
        <div class="type-grid">
            <?php foreach ($types as $t): ?>
                <a class="type-card" href="<?= url('horoscopes.php?type=' . $t['slug']) ?>">
                    <span class="type-icon"><?= icon(typeIcon($t['slug'])) ?></span>
                    <span><?= e($t['name']) ?> гороскоп</span>
                    <?= icon('arrow-right', 'ico ico-arrow') ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="cta-card">
            <div>
                <h2>Персональный гороскоп по дате и месту рождения</h2>
                <p class="lead">Рассчитаем положение Солнца, Луны и асцендента в вашей натальной карте. Координаты города определяются автоматически.</p>
            </div>
            <a class="btn btn-gold" href="<?= url('personal.php') ?>">Рассчитать карту <?= icon('arrow-right') ?></a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Астрологи портала</h2>
            <a class="link" href="<?= url('astrologers.php') ?>">Все астрологи <?= icon('arrow-right') ?></a>
        </div>
        <div class="astro-grid">
            <?php foreach ($astrologers as $a): ?>
                <?php include 'includes/astrologer_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Астропрогнозы и статьи</h2>
            <a class="link" href="<?= url('articles.php') ?>">Все статьи <?= icon('arrow-right') ?></a>
        </div>
        <div class="article-grid">
            <?php foreach ($articles as $art): ?>
                <?php include 'includes/article_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
