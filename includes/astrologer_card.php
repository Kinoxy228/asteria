<?php /** @var array $a */ ?>
<article class="astro-card">
    <img class="avatar" src="<?= url('assets/img/avatars/' . $a['photo']) ?>" alt="<?= e($a['name']) ?>" width="96" height="96">
    <div class="astro-body">
        <span class="badge"><?= e($a['specialization']) ?></span>
        <h3><?= e($a['name']) ?></h3>
        <div class="astro-meta">
            <span><?= icon('star') ?> <?= e(number_format((float)$a['rating'], 1, ',', '')) ?></span>
            <span><?= icon('clock-outline') ?> стаж <?= (int)$a['experience'] ?> лет</span>
        </div>
        <p class="muted small"><?= e(mb_strimwidth($a['about'], 0, 110, '…')) ?></p>
        <div class="astro-foot">
            <span class="price">от <?= number_format((int)$a['price'], 0, ',', ' ') ?> ₽</span>
            <a class="btn btn-outline btn-sm" href="<?= url('astrologer.php?id=' . $a['id']) ?>">Подробнее</a>
        </div>
    </div>
</article>
