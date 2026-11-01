<?php
require 'includes/db.php';
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM astrologers WHERE id = ?');
$st->execute([$id]);
$a = $st->fetch();
if (!$a) {
    http_response_code(404);
    $pageTitle = 'Астролог не найден';
    require 'includes/header.php';
    echo '<section class="section"><div class="container"><h1>Астролог не найден</h1></div></section>';
    require 'includes/footer.php';
    exit;
}

$reviewErrors = [];
$bookErrors = [];
$book = ['date' => '', 'time' => '', 'topic' => '', 'format' => 'Онлайн'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    if (($_POST['action'] ?? '') === 'review') {
        $rating = (int)($_POST['rating'] ?? 0);
        $text = trim($_POST['text'] ?? '');
        if ($rating < 1 || $rating > 5) $reviewErrors[] = 'Поставьте оценку от 1 до 5.';
        if (mb_strlen($text) < 10) $reviewErrors[] = 'Текст отзыва должен содержать не менее 10 символов.';
        if (!$reviewErrors) {
            $pdo->prepare('INSERT INTO reviews (astrologer_id, user_id, rating, text, is_approved) VALUES (?, ?, ?, ?, 0)')
                ->execute([$id, $_SESSION['user_id'], $rating, $text]);
            flash('success', 'Спасибо! Отзыв появится после проверки модератором.');
            header('Location: ' . url('astrologer.php?id=' . $id . '#reviews'));
            exit;
        }
    }
    if (($_POST['action'] ?? '') === 'book') {
        foreach ($book as $k => $v) $book[$k] = trim($_POST[$k] ?? '');
        if (!$book['date'] || strtotime($book['date']) < strtotime(date('Y-m-d'))) $bookErrors[] = 'Выберите дату консультации не раньше сегодняшнего дня.';
        if (!preg_match('/^\d{2}:\d{2}$/', $book['time'])) $bookErrors[] = 'Выберите время консультации.';
        if (mb_strlen($book['topic']) < 3) $bookErrors[] = 'Опишите тему консультации.';
        if (!in_array($book['format'], ['Онлайн', 'Чат'], true)) $bookErrors[] = 'Выберите формат консультации.';
        if (!$bookErrors) {
            $pdo->prepare('INSERT INTO consultations (user_id, astrologer_id, consult_date, consult_time, topic, format, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$_SESSION['user_id'], $id, $book['date'], $book['time'] . ':00', $book['topic'], $book['format'], 'Новая']);
            flash('success', 'Заявка на консультацию отправлена. Статус можно отслеживать в личном кабинете.');
            header('Location: ' . url('account.php'));
            exit;
        }
    }
}

// Отзывы о конкретном астрологе
$rv = $pdo->prepare('SELECT r.*, u.name AS author FROM reviews r JOIN users u ON u.id = r.user_id
    WHERE r.astrologer_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC');
$rv->execute([$id]);
$reviews = $rv->fetchAll();
$avg = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

$art = $pdo->prepare('SELECT * FROM articles WHERE astrologer_id = ? ORDER BY published_at DESC');
$art->execute([$id]);
$articles = $art->fetchAll();

$pageTitle = $a['name'];
require 'includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <nav class="crumbs"><a href="<?= url('index.php') ?>">Главная</a> / <a href="<?= url('astrologers.php') ?>">Астрологи</a> / <span><?= e($a['name']) ?></span></nav>
    </div>
</section>
<section class="section pt-0">
    <div class="container profile-grid">
        <div>
            <div class="card profile-card">
                <img class="avatar avatar-lg" src="<?= url('assets/img/avatars/' . $a['photo']) ?>" alt="<?= e($a['name']) ?>">
                <div>
                    <span class="badge"><?= e($a['specialization']) ?></span>
                    <h1><?= e($a['name']) ?></h1>
                    <div class="astro-meta">
                        <span><?= icon('star') ?> <?= number_format((float)$a['rating'], 1, ',', '') ?></span>
                        <span><?= icon('message-text-outline') ?> <?= count($reviews) ?> отзывов</span>
                        <span><?= icon('clock-outline') ?> стаж <?= (int)$a['experience'] ?> лет</span>
                    </div>
                    <p><?= e($a['about']) ?></p>
                </div>
            </div>

            <div class="card" id="reviews">
                <div class="section-head">
                    <h2>Отзывы</h2>
                    <?php if ($reviews): ?><div class="avg"><strong><?= number_format($avg, 1, ',', '') ?></strong> <?= stars((int)round($avg)) ?></div><?php endif; ?>
                </div>
                <?php foreach ($reviews as $r): ?>
                    <div class="review">
                        <div class="review-head">
                            <span class="review-avatar"><?= e(mb_substr($r['author'], 0, 1)) ?></span>
                            <div><strong><?= e($r['author']) ?></strong><div class="muted small"><?= ruDate($r['created_at']) ?></div></div>
                            <?= stars((int)$r['rating']) ?>
                        </div>
                        <p><?= e($r['text']) ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (!$reviews): ?><p class="muted">Отзывов пока нет — станьте первым!</p><?php endif; ?>

                <?php if (isLoggedIn()): ?>
                    <form method="post" class="review-form" novalidate>
                        <h3>Оставить отзыв</h3>
                        <?php foreach ($reviewErrors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                        <input type="hidden" name="action" value="review">
                        <div class="rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="r<?= $i ?>" name="rating" value="<?= $i ?>"><label for="r<?= $i ?>" title="<?= $i ?>"><?= icon('star') ?></label>
                            <?php endfor; ?>
                        </div>
                        <textarea name="text" rows="3" placeholder="Расскажите о консультации"><?= e($_POST['text'] ?? '') ?></textarea>
                        <button class="btn btn-outline" type="submit"><?= icon('send') ?>Отправить на модерацию</button>
                    </form>
                <?php else: ?>
                    <p class="muted small"><a class="link" href="<?= url('login.php') ?>">Войдите</a>, чтобы оставить отзыв.</p>
                <?php endif; ?>
            </div>
        </div>

        <aside>
            <form class="card form-card sticky" method="post" novalidate>
                <h2>Записаться на консультацию</h2>
                <div class="price-big"><?= number_format((int)$a['price'], 0, ',', ' ') ?> ₽ <span class="muted small">/ 60 минут</span></div>
                <?php foreach ($bookErrors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <input type="hidden" name="action" value="book">
                <div class="form-row">
                    <label>Дата<input type="date" name="date" min="<?= date('Y-m-d') ?>" value="<?= e($book['date']) ?>"></label>
                    <label>Время<input type="time" name="time" value="<?= e($book['time']) ?>"></label>
                </div>
                <label>Тема консультации<input type="text" name="topic" value="<?= e($book['topic']) ?>" placeholder="Например, разбор натальной карты"></label>
                <label>Формат
                    <select name="format">
                        <option <?= $book['format'] === 'Онлайн' ? 'selected' : '' ?>>Онлайн</option>
                        <option <?= $book['format'] === 'Чат' ? 'selected' : '' ?>>Чат</option>
                    </select>
                </label>
                <?php if (isLoggedIn()): ?>
                    <button class="btn btn-gold btn-block" type="submit"><?= icon('calendar-check-outline') ?>Записаться</button>
                <?php else: ?>
                    <a class="btn btn-gold btn-block" href="<?= url('login.php') ?>">Войдите, чтобы записаться</a>
                <?php endif; ?>
            </form>
            <?php if ($articles): ?>
                <div class="card">
                    <h3>Статьи автора</h3>
                    <?php foreach ($articles as $ar): ?>
                        <a class="side-link" href="<?= url('article.php?slug=' . $ar['slug']) ?>"><?= icon('book-open-page-variant-outline') ?><?= e($ar['title']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
