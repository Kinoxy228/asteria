<?php /** @var array $art */ ?>
<a class="article-card" href="<?= url('article.php?slug=' . $art['slug']) ?>">
    <img src="<?= url('assets/img/articles/' . $art['image']) ?>" alt="" loading="lazy">
    <div class="article-body">
        <div class="article-meta">
            <span class="badge"><?= e($art['category']) ?></span>
            <span class="muted small"><?= ruDate($art['published_at']) ?></span>
        </div>
        <h3><?= e($art['title']) ?></h3>
        <p class="muted small"><?= e($art['excerpt']) ?></p>
    </div>
</a>
