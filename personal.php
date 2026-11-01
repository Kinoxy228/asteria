<?php
require 'includes/db.php';
require_once 'includes/functions.php';
require 'includes/astro.php';
require 'includes/geocode.php';
require 'includes/chart.php';

$errors = [];
$chart = null;
$place = null;

// Значения по умолчанию — из профиля пользователя
$form = ['name' => '', 'birth_date' => '', 'birth_time' => '12:00', 'birth_place' => ''];
if (isLoggedIn()) {
    $u = $pdo->prepare('SELECT name, birth_date, birth_time, birth_place FROM users WHERE id = ?');
    $u->execute([$_SESSION['user_id']]);
    $profile = $u->fetch();
    $form['name'] = $profile['name'];
    $form['birth_date'] = $profile['birth_date'] ?? '';
    $form['birth_time'] = $profile['birth_time'] ? substr($profile['birth_time'], 0, 5) : '12:00';
    $form['birth_place'] = $profile['birth_place'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $v) $form[$k] = trim($_POST[$k] ?? '');

    if ($form['name'] === '') $errors[] = 'Введите имя.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['birth_date']) || strtotime($form['birth_date']) > time()) {
        $errors[] = 'Укажите корректную дату рождения.';
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $form['birth_time'])) $errors[] = 'Укажите время рождения в формате ЧЧ:ММ.';
    if (mb_strlen($form['birth_place']) < 2) $errors[] = 'Укажите город рождения.';

    if (!$errors) {
        // Обращение к внешнему API геокодирования
        $place = geocodePlace($form['birth_place'], $apiError);
        if (!$place) {
            $errors[] = $apiError;
        } else {
            $chart = buildNatalChart($pdo, $form['birth_date'], $form['birth_time'], $place);

            // Сохранение карты в личный кабинет
            if (isset($_POST['save']) && isLoggedIn()) {
                $pdo->prepare('DELETE FROM natal_charts WHERE user_id = ?')->execute([$_SESSION['user_id']]);
                $ins = $pdo->prepare('INSERT INTO natal_charts (user_id, birth_date, birth_time, birth_place, latitude, longitude, timezone,
                    sun_sign_id, moon_sign_id, ascendant_sign_id, sun_degree, moon_degree, asc_degree)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $ins->execute([
                    $_SESSION['user_id'], $form['birth_date'], $form['birth_time'] . ':00',
                    $place['name'], $place['latitude'], $place['longitude'], $place['timezone'],
                    $chart['sun']['sign']['id'], $chart['moon']['sign']['id'], $chart['asc']['sign']['id'],
                    $chart['sun']['degree'], $chart['moon']['degree'], $chart['asc']['degree'],
                ]);
                $pdo->prepare('UPDATE users SET birth_date = ?, birth_time = ?, birth_place = ? WHERE id = ?')
                    ->execute([$form['birth_date'], $form['birth_time'] . ':00', $place['name'], $_SESSION['user_id']]);
                flash('success', 'Натальная карта сохранена в личном кабинете.');
                header('Location: ' . url('account.php'));
                exit;
            }
        }
    }
}

// Персональный гороскоп на сегодня по знаку Солнца
$todayHoro = [];
if ($chart) {
    $st = $pdo->prepare("SELECT h.content, h.rating, t.name, t.slug FROM horoscopes h JOIN horoscope_types t ON t.id = h.type_id
        WHERE h.period = 'day' AND h.date_from = ? AND h.sign_id = ? ORDER BY t.id");
    $st->execute([date('Y-m-d'), $chart['sun']['sign']['id']]);
    $todayHoro = $st->fetchAll();
}

$pageTitle = 'Персональный гороскоп';
require 'includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <nav class="crumbs"><a href="<?= url('index.php') ?>">Главная</a> / <span>Персональный гороскоп</span></nav>
        <h1>Персональный гороскоп</h1>
        <p class="lead">Введите дату, время и город рождения — мы построим вашу натальную карту и подберём гороскоп на сегодня.</p>
    </div>
</section>

<section class="section pt-0">
    <div class="container personal-grid">
        <form class="card form-card" method="post" novalidate>
            <h2>Данные рождения</h2>
            <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= icon('alert-circle-outline') ?><?= e($err) ?></div><?php endforeach; ?>
            <label>Имя
                <input type="text" name="name" value="<?= e($form['name']) ?>" placeholder="Как к вам обращаться">
            </label>
            <div class="form-row">
                <label>Дата рождения
                    <input type="date" name="birth_date" value="<?= e($form['birth_date']) ?>" max="<?= date('Y-m-d') ?>">
                </label>
                <label>Время рождения
                    <input type="time" name="birth_time" value="<?= e($form['birth_time']) ?>">
                </label>
            </div>
            <label>Город рождения
                <input type="text" name="birth_place" value="<?= e($form['birth_place']) ?>" placeholder="Например, Москва">
                <span class="hint"><?= icon('map-marker-outline') ?>Координаты и часовой пояс определяются через Open-Meteo Geocoding API</span>
            </label>
            <button class="btn btn-gold btn-block" type="submit"><?= icon('chart-donut') ?>Построить натальную карту</button>
            <?php if ($chart && isLoggedIn()): ?>
                <button class="btn btn-outline btn-block" type="submit" name="save" value="1"><?= icon('check-circle-outline') ?>Сохранить в личный кабинет</button>
            <?php elseif ($chart): ?>
                <p class="muted small center"><a class="link" href="<?= url('login.php') ?>">Войдите</a>, чтобы сохранить карту в личном кабинете.</p>
            <?php endif; ?>
        </form>

        <div class="personal-result">
            <?php if ($chart): ?>
                <div class="card chart-card">
                    <div class="chart-head">
                        <div>
                            <h2>Натальная карта<?= $form['name'] ? ': ' . e($form['name']) : '' ?></h2>
                            <p class="muted small"><?= ruDate($form['birth_date']) ?>, <?= e($form['birth_time']) ?> · <?= e($place['name']) ?><?= $place['region'] ? ', ' . e($place['region']) : '' ?><?= $place['country'] ? ', ' . e($place['country']) : '' ?></p>
                        </div>
                    </div>
                    <div class="chart-body">
                        <?= natalWheel($chart) ?>
                        <div class="chart-points">
                            <?php foreach (['sun' => 'Солнце', 'moon' => 'Луна', 'asc' => 'Асцендент'] as $key => $title): $p = $chart[$key]; ?>
                                <div class="point">
                                    <span class="sign-glyph"><?= zodiacIcon($p['sign']['slug']) ?></span>
                                    <div>
                                        <div class="muted small"><?= $title ?></div>
                                        <strong><?= e($p['sign']['name']) ?> <?= degreeText($p['degree']) ?></strong>
                                        <p class="muted small"><?= e(chartMeaning($key, $p['sign'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="api-box">
                                <?= icon('web') ?>
                                <div class="small">
                                    <strong>Данные API:</strong> широта <?= number_format($place['latitude'], 4, ',', '') ?>, долгота <?= number_format($place['longitude'], 4, ',', '') ?>,
                                    часовой пояс <?= e($place['timezone']) ?>. Время рождения по UTC: <?= e($chart['utc']) ?>.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <h2>Гороскоп на сегодня для знака <?= e($chart['sun']['sign']['name']) ?></h2>
                    <div class="today-types">
                        <?php foreach ($todayHoro as $h): ?>
                            <div class="today-type">
                                <span class="type-icon sm"><?= icon(typeIcon($h['slug'])) ?></span>
                                <div><strong><?= e($h['name']) ?></strong> <?= stars((int)$h['rating']) ?><p class="muted small"><?= e($h['content']) ?></p></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card placeholder-card">
                    <img src="<?= url('assets/img/articles/ascendant.svg') ?>" alt="">
                    <h2>Что покажет натальная карта</h2>
                    <ul class="check-list">
                        <li><?= icon('white-balance-sunny') ?><span><strong>Солнце</strong> — основа характера и жизненные цели</span></li>
                        <li><?= icon('moon-waning-crescent') ?><span><strong>Луна</strong> — эмоции, привычки и внутренние потребности</span></li>
                        <li><?= icon('telescope') ?><span><strong>Асцендент</strong> — первое впечатление и манера поведения</span></li>
                        <li><?= icon('calendar-check-outline') ?><span>Персональный гороскоп на сегодня по вашему знаку</span></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
