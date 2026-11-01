<?php
require 'includes/db.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$st = $pdo->prepare('SELECT ar.*, a.name AS author, a.photo, a.specialization, a.id AS author_id
    FROM articles ar LEFT JOIN astrologers a ON a.id = ar.astrologer_id WHERE ar.slug = ?');
$st->execute([$slug]);
$article = $st->fetch();
if (!$article) {
    header('Location: ' . url('articles.php'));
    exit;
}
// Счётчик просмотров
$pdo->prepare('UPDATE articles SET views = views + 1 WHERE id = ?')->execute([$article['id']]);

$more = $pdo->prepare('SELECT * FROM articles WHERE id <> ? ORDER BY published_at DESC LIMIT 3');
$more->execute([$article['id']]);
$moreArticles = $more->fetchAll();

$pageTitle = $article['title'];
require 'includes/header.php';
?>
<section class="page-head">
    <div class="container narrow">
        <nav class="crumbs"><a href="<?= url('index.php') ?>">Главная</a> / <a href="<?= url('articles.php') ?>">Статьи</a> / <span><?= e($article['category']) ?></span></nav>
        <span class="badge"><?= e($article['category']) ?></span>
        <h1><?= e($article['title']) ?></h1>
        <div class="article-meta">
            <span class="muted small"><?= icon('calendar-today') ?> <?= ruDate($article['published_at']) ?></span>
            <span class="muted small"><?= icon('eye-outline') ?> <?= (int)$article['views'] + 1 ?> просмотров</span>
        </div>
    </div>
</section>
<section class="section pt-0">
    <div class="container narrow">
        <img class="article-cover" src="<?= url('assets/img/articles/' . $article['image']) ?>" alt="">
        <p class="lead"><?= e($article['excerpt']) ?></p>
        <div class="article-text">
            <?php foreach (preg_split('/\n\s*\n/', $article['content']) as $para): ?>
                <p><?= e($para) ?></p>
            <?php endforeach; ?>
        </div>
        <?php if ($article['author']): ?>
            <div class="card author-card">
                <img class="avatar" src="<?= url('assets/img/avatars/' . $article['photo']) ?>" alt="">
                <div>
                    <div class="muted small">Автор статьи</div>
                    <strong><?= e($article['author']) ?></strong>
                    <div class="muted small"><?= e($article['specialization']) ?></div>
                </div>
                <a class="btn btn-outline btn-sm" href="<?= url('astrologer.php?id=' . $article['author_id']) ?>">Записаться на консультацию</a>
            </div>
        <?php endif; ?>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="section-head"><h2>Читайте также</h2></div>
        <div class="article-grid">
            <?php foreach ($moreArticles as $art): ?>
                <?php include 'includes/article_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
