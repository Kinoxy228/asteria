<?php
require 'includes/db.php';
require_once 'includes/functions.php';

$cat = $_GET['cat'] ?? '';
$q = trim($_GET['q'] ?? '');
$cats = $pdo->query('SELECT category, COUNT(*) AS cnt FROM articles GROUP BY category ORDER BY category')->fetchAll();

$where = [];
$params = [];
if ($cat !== '') { $where[] = 'category = ?'; $params[] = $cat; }
if ($q !== '') { $where[] = '(title LIKE ? OR excerpt LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
$sql = 'SELECT * FROM articles' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY published_at DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$articles = $st->fetchAll();

$pageTitle = 'Статьи и астропрогнозы';
require 'includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <nav class="crumbs"><a href="<?= url('index.php') ?>">Главная</a> / <span>Статьи</span></nav>
        <h1>Статьи и астропрогнозы</h1>
        <p class="lead">Прогнозы на месяц, разборы натальной карты и советы астрологов портала.</p>
    </div>
</section>
<section class="section pt-0">
    <div class="container">
        <div class="filters-bar">
            <div class="pills">
                <a class="pill <?= $cat === '' ? 'active' : '' ?>" href="<?= url('articles.php') ?>">Все</a>
                <?php foreach ($cats as $c): ?>
                    <a class="pill <?= $cat === $c['category'] ? 'active' : '' ?>" href="<?= url('articles.php?cat=' . urlencode($c['category'])) ?>"><?= e($c['category']) ?> <span class="count"><?= $c['cnt'] ?></span></a>
                <?php endforeach; ?>
            </div>
            <form class="search-form" method="get">
                <?php if ($cat !== ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
                <?= icon('magnify') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Поиск по статьям">
            </form>
        </div>
        <?php if ($articles): ?>
            <div class="article-grid">
                <?php foreach ($articles as $art): ?>
                    <?php include 'includes/article_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="muted">По вашему запросу ничего не найдено.</p>
        <?php endif; ?>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
