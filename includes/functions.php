<?php
// Общие функции портала «Астерия»
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Корень сайта относительно сервера (работает и в /asteria/, и в /asteria/admin/)
define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'], basename(dirname($_SERVER['SCRIPT_FILENAME'])) === 'admin' ? 2 : 1)), '/'));

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

// Вставка SVG-иконки из папки assets/img/icons
function icon(string $name, string $class = 'ico'): string
{
    static $cache = [];
    if (!isset($cache[$name])) {
        $file = __DIR__ . '/../assets/img/icons/' . $name . '.svg';
        $svg = is_file($file) ? file_get_contents($file) : '';
        $svg = preg_replace('/ id="[^"]*"/', '', $svg);
        $cache[$name] = $svg;
    }
    return str_replace('<svg ', '<svg class="' . e($class) . '" aria-hidden="true" fill="currentColor" ', $cache[$name]);
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . url('login.php'));
        exit;
    }
}

function flash(string $type, string $text): void
{
    $_SESSION['flash'][] = ['type' => $type, 'text' => $text];
}

function showFlash(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $html .= '<div class="alert alert-' . e($f['type']) . '">' . e($f['text']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

// Знак зодиака по дате рождения (по таблице zodiac_signs)
function signByDate(PDO $pdo, string $date): ?array
{
    $md = date('m-d', strtotime($date));
    foreach ($pdo->query('SELECT * FROM zodiac_signs ORDER BY id') as $s) {
        if ($s['date_start'] <= $s['date_end']) {
            if ($md >= $s['date_start'] && $md <= $s['date_end']) return $s;
        } elseif ($md >= $s['date_start'] || $md <= $s['date_end']) {
            return $s; // Козерог переходит через Новый год
        }
    }
    return null;
}

function allSigns(PDO $pdo): array
{
    static $signs = null;
    if ($signs === null) {
        $signs = $pdo->query('SELECT * FROM zodiac_signs ORDER BY id')->fetchAll();
    }
    return $signs;
}

function signPeriod(array $s): string
{
    $m = ['01' => 'янв', '02' => 'фев', '03' => 'мар', '04' => 'апр', '05' => 'мая', '06' => 'июн',
          '07' => 'июл', '08' => 'авг', '09' => 'сен', '10' => 'окт', '11' => 'ноя', '12' => 'дек'];
    [$m1, $d1] = explode('-', $s['date_start']);
    [$m2, $d2] = explode('-', $s['date_end']);
    return (int)$d1 . ' ' . $m[$m1] . ' – ' . (int)$d2 . ' ' . $m[$m2];
}

function ruDate(string $date, bool $withYear = true): string
{
    $months = ['', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    $t = strtotime($date);
    return (int)date('j', $t) . ' ' . $months[(int)date('n', $t)] . ($withYear ? ' ' . date('Y', $t) : '');
}

function weekdayRu(string $date): string
{
    $d = ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'];
    return $d[(int)date('w', strtotime($date))];
}

function stars(int $n, int $max = 5): string
{
    $html = '<span class="stars" title="' . $n . ' из ' . $max . '">';
    for ($i = 1; $i <= $max; $i++) {
        $html .= icon($i <= $n ? 'star' : 'star-outline', 'ico ico-star');
    }
    return $html . '</span>';
}

// Фаза Луны (0 — новолуние, 0.5 — полнолуние)
function moonPhase(?int $ts = null): array
{
    $ts = $ts ?? time();
    $synodic = 29.530588853;
    $knownNew = gmmktime(18, 14, 0, 1, 6, 2000);
    $age = fmod(($ts - $knownNew) / 86400, $synodic);
    if ($age < 0) $age += $synodic;
    $phase = $age / $synodic;
    $names = [
        [0.03, 'Новолуние'], [0.22, 'Растущий серп'], [0.28, 'Первая четверть'], [0.47, 'Растущая Луна'],
        [0.53, 'Полнолуние'], [0.72, 'Убывающая Луна'], [0.78, 'Последняя четверть'], [0.97, 'Убывающий серп'], [1.01, 'Новолуние'],
    ];
    foreach ($names as [$limit, $name]) {
        if ($phase < $limit) break;
    }
    return ['age' => round($age, 1), 'phase' => $phase, 'name' => $name, 'day' => (int)floor($age) + 1];
}

function typeIcon(string $slug): string
{
    return ['general' => 'star-four-points', 'love' => 'heart-outline', 'career' => 'briefcase-outline',
            'health' => 'leaf', 'finance' => 'cash'][$slug] ?? 'star-four-points';
}

function elementIcon(string $el): string
{
    return ['Огонь' => 'fire', 'Земля' => 'earth', 'Воздух' => 'weather-windy', 'Вода' => 'water'][$el] ?? 'star';
}

function zodiacIcon(string $slug, string $class = 'ico'): string
{
    return icon('zodiac-' . $slug, $class);
}
