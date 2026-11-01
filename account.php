<?php
require 'includes/db.php';
require_once 'includes/functions.php';
require 'includes/astro.php';
require 'includes/chart.php';
requireLogin();

$uid = $_SESSION['user_id'];

// Отмена консультации пользователем
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    $pdo->prepare("UPDATE consultations SET status = 'Отменена' WHERE id = ? AND user_id = ? AND status IN ('Новая', 'Подтверждена')")
        ->execute([(int)$_POST['cancel'], $uid]);
    flash('info', 'Консультация отменена.');
    header('Location: ' . url('account.php#consultations'));
    exit;
}

$u = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$u->execute([$uid]);
$user = $u->fetch();
$sign = $user['birth_date'] ? signByDate($pdo, $user['birth_date']) : null;

// Моя натальная карта
$nc = $pdo->prepare('SELECT n.*, s1.name AS sun_name, s1.slug AS sun_slug, s2.name AS moon_name, s2.slug AS moon_slug,
        s3.name AS asc_name, s3.slug AS asc_slug
    FROM natal_charts n
    JOIN zodiac_signs s1 ON s1.id = n.sun_sign_id
    JOIN zodiac_signs s2 ON s2.id = n.moon_sign_id
    JOIN zodiac_signs s3 ON s3.id = n.ascendant_sign_id
    WHERE n.user_id = ?');
$nc->execute([$uid]);
$natal = $nc->fetch();
$chart = null;
if ($natal) {
    $signs = allSigns($pdo);
    $chart = [];
    foreach (['sun' => 'sun_sign_id', 'moon' => 'moon_sign_id', 'asc' => 'ascendant_sign_id'] as $k => $col) {
        $deg = (float)$natal[$k === 'asc' ? 'asc_degree' : $k . '_degree'];
        $chart[$k] = ['longitude' => ($natal[$col] - 1) * 30 + $deg, 'degree' => $deg, 'sign' => $signs[$natal[$col] - 1]];
    }
}

// Мои консультации
$cs = $pdo->prepare('SELECT c.*, a.name AS astrologer, a.photo, a.specialization FROM consultations c
    JOIN astrologers a ON a.id = c.astrologer_id WHERE c.user_id = ? ORDER BY c.consult_date DESC, c.consult_time DESC');
$cs->execute([$uid]);
$consultations = $cs->fetchAll();

$statusClass = ['Новая' => 'st-new', 'Подтверждена' => 'st-ok', 'Завершена' => 'st-done', 'Отменена' => 'st-cancel'];
$pageTitle = 'Личный кабинет';
require 'includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <nav class="crumbs"><a href="<?= url('index.php') ?>">Главная</a> / <span>Личный кабинет</span></nav>
        <h1>Личный кабинет</h1>
    </div>
</section>
<section class="section pt-0">
    <div class="container account-grid">
        <aside class="card profile-side">
            <div class="profile-avatar"><?= e(mb_substr($user['name'], 0, 1)) ?></div>
            <h2><?= e($user['name']) ?></h2>
            <p class="muted small"><?= e($user['email']) ?></p>
            <dl>
                <dt>Дата рождения</dt><dd><?= $user['birth_date'] ? ruDate($user['birth_date']) : '—' ?></dd>
                <dt>Знак зодиака</dt><dd><?= $sign ? zodiacIcon($sign['slug']) . ' ' . e($sign['name']) : '—' ?></dd>
                <dt>На портале с</dt><dd><?= ruDate($user['created_at']) ?></dd>
            </dl>
            <?php if ($sign): ?><a class="btn btn-outline btn-block" href="<?= url('horoscopes.php?sign=' . $sign['slug']) ?>">Мой гороскоп на сегодня</a><?php endif; ?>
        </aside>

        <div class="account-main">
            <div class="card">
                <div class="section-head">
                    <h2><?= icon('chart-donut') ?> Моя натальная карта</h2>
                    <a class="link" href="<?= url('personal.php') ?>"><?= $natal ? 'Пересчитать' : 'Рассчитать' ?> <?= icon('arrow-right') ?></a>
                </div>
                <?php if ($natal): ?>
                    <div class="chart-body">
                        <?= natalWheel($chart, 340) ?>
                        <div class="chart-points">
                            <p class="muted small"><?= ruDate($natal['birth_date']) ?>, <?= substr($natal['birth_time'], 0, 5) ?> · <?= e($natal['birth_place']) ?> (<?= number_format((float)$natal['latitude'], 2, ',', '') ?>°, <?= number_format((float)$natal['longitude'], 2, ',', '') ?>°)</p>
                            <?php foreach (['sun' => 'Солнце', 'moon' => 'Луна', 'asc' => 'Асцендент'] as $k => $title): ?>
                                <div class="point">
                                    <span class="sign-glyph"><?= zodiacIcon($chart[$k]['sign']['slug']) ?></span>
                                    <div>
                                        <div class="muted small"><?= $title ?></div>
                                        <strong><?= e($chart[$k]['sign']['name']) ?> <?= degreeText($chart[$k]['degree']) ?></strong>
                                        <p class="muted small"><?= e(chartMeaning($k, $chart[$k]['sign'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="muted">Натальная карта ещё не рассчитана. Укажите дату, время и место рождения в разделе «Персональный гороскоп».</p>
                <?php endif; ?>
            </div>

            <div class="card" id="consultations">
                <div class="section-head">
                    <h2><?= icon('calendar-check-outline') ?> Мои консультации</h2>
                    <a class="link" href="<?= url('astrologers.php') ?>">Записаться <?= icon('arrow-right') ?></a>
                </div>
                <?php if ($consultations): ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Астролог</th><th>Дата и время</th><th>Тема</th><th>Формат</th><th>Статус</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($consultations as $c): ?>
                                <tr>
                                    <td class="who"><img class="avatar avatar-xs" src="<?= url('assets/img/avatars/' . $c['photo']) ?>" alt=""><div><strong><?= e($c['astrologer']) ?></strong><div class="muted small"><?= e($c['specialization']) ?></div></div></td>
                                    <td><?= ruDate($c['consult_date']) ?>, <?= substr($c['consult_time'], 0, 5) ?></td>
                                    <td><?= e($c['topic']) ?></td>
                                    <td><?= e($c['format']) ?></td>
                                    <td><span class="status <?= $statusClass[$c['status']] ?>"><?= e($c['status']) ?></span></td>
                                    <td>
                                        <?php if (in_array($c['status'], ['Новая', 'Подтверждена'], true)): ?>
                                            <form method="post" onsubmit="return confirm('Отменить консультацию?')"><button class="btn btn-ghost btn-sm" name="cancel" value="<?= $c['id'] ?>">Отменить</button></form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="muted">У вас пока нет консультаций.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
