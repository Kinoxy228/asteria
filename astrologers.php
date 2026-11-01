<?php
require 'includes/db.php';
require_once 'includes/functions.php';

$spec = $_GET['spec'] ?? '';
$sort = $_GET['sort'] ?? 'rating';
$orders = ['rating' => 'rating DESC, experience DESC', 'experience' => 'experience DESC', 'price' => 'price ASC'];
$order = $orders[$sort] ?? $orders['rating'];

// Специализации с количеством астрологов
$specs = $pdo->query('SELECT specialization, COUNT(*) AS cnt FROM astrologers GROUP BY specialization ORDER BY specialization')->fetchAll();

// Астрологи по выбранной специализации
if ($spec !== '') {
    $stmt = $pdo->prepare("SELECT a.*, (SELECT COUNT(*) FROM reviews r WHERE r.astrologer_id = a.id AND r.is_approved = 1) AS reviews_count
        FROM astrologers a WHERE a.specialization = ? ORDER BY $order");
    $stmt->execute([$spec]);
} else {
    $stmt = $pdo->query("SELECT a.*, (SELECT COUNT(*) FROM reviews r WHERE r.astrologer_id = a.id AND r.is_approved = 1) AS reviews_count
        FROM astrologers a ORDER BY $order");
}
$astrologers = $stmt->fetchAll();
$total = array_sum(array_column($specs, 'cnt'));

$pageTitle = 'Астрологи';
require 'includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <nav class="crumbs"><a href="<?= url('index.php') ?>">Главная</a> / <span>Астрологи</span></nav>
        <h1>Астрологи</h1>
        <p class="lead">Выберите специалиста по нужному направлению и запишитесь на онлайн-консультацию.</p>
    </div>
</section>
<section class="section pt-0">
    <div class="container">
        <div class="filters-bar">
            <div class="pills">
                <a class="pill <?= $spec === '' ? 'active' : '' ?>" href="<?= url('astrologers.php?sort=' . e($sort)) ?>">Все <span class="count"><?= $total ?></span></a>
                <?php foreach ($specs as $s): ?>
                    <a class="pill <?= $spec === $s['specialization'] ? 'active' : '' ?>" href="<?= url('astrologers.php?' . http_build_query(['spec' => $s['specialization'], 'sort' => $sort])) ?>"><?= e($s['specialization']) ?> <span class="count"><?= $s['cnt'] ?></span></a>
                <?php endforeach; ?>
            </div>
            <form class="sort-form" method="get">
                <?php if ($spec !== ''): ?><input type="hidden" name="spec" value="<?= e($spec) ?>"><?php endif; ?>
                <label class="muted small" for="sort">Сортировка</label>
                <select id="sort" name="sort" onchange="this.form.submit()">
                    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>по рейтингу</option>
                    <option value="experience" <?= $sort === 'experience' ? 'selected' : '' ?>>по стажу</option>
                    <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>по цене</option>
                </select>
            </form>
        </div>
        <div class="astro-grid">
            <?php foreach ($astrologers as $a): ?>
                <?php include 'includes/astrologer_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
