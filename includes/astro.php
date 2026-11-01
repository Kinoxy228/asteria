<?php
// Расчёт упрощённой натальной карты: положение Солнца, Луны и асцендента.
// Используются формулы низкой точности из книги Ж. Миуса «Астрономические алгоритмы»
// (погрешность около 1°, чего достаточно для определения знака зодиака).

function norm360(float $x): float
{
    $x = fmod($x, 360.0);
    return $x < 0 ? $x + 360.0 : $x;
}

// Юлианская дата по времени UTC
function julianDay(DateTime $utc): float
{
    return $utc->getTimestamp() / 86400.0 + 2440587.5;
}

function sunLongitude(float $jd): float
{
    $t = ($jd - 2451545.0) / 36525.0;
    $l0 = 280.46646 + 36000.76983 * $t;
    $m = deg2rad(357.52911 + 35999.05029 * $t);
    $c = (1.914602 - 0.004817 * $t) * sin($m) + 0.019993 * sin(2 * $m) + 0.000289 * sin(3 * $m);
    $omega = deg2rad(125.04 - 1934.136 * $t);
    return norm360($l0 + $c - 0.00569 - 0.00478 * sin($omega));
}

function moonLongitude(float $jd): float
{
    $t = ($jd - 2451545.0) / 36525.0;
    $l = 218.3164477 + 481267.88123421 * $t;
    $d = deg2rad(297.8501921 + 445267.1114034 * $t);
    $m = deg2rad(357.5291092 + 35999.0502909 * $t);
    $mm = deg2rad(134.9633964 + 477198.8675055 * $t);
    $f = deg2rad(93.2720950 + 483202.0175233 * $t);
    $lon = $l + 6.289 * sin($mm) + 1.274 * sin(2 * $d - $mm) + 0.658 * sin(2 * $d) + 0.214 * sin(2 * $mm)
        - 0.186 * sin($m) - 0.114 * sin(2 * $f);
    return norm360($lon);
}

function ascendant(float $jd, float $lat, float $lon): float
{
    $t = ($jd - 2451545.0) / 36525.0;
    $gmst = 280.46061837 + 360.98564736629 * ($jd - 2451545.0) + 0.000387933 * $t * $t;
    $ramc = deg2rad(norm360($gmst + $lon));          // местное звёздное время
    $eps = deg2rad(23.4393 - 0.0130 * $t);           // наклон эклиптики
    $phi = deg2rad($lat);
    $asc = atan2(cos($ramc), -(sin($ramc) * cos($eps) + tan($phi) * sin($eps)));
    return norm360(rad2deg($asc));
}

/**
 * Построение натальной карты.
 * $place — результат геокодирования: latitude, longitude, timezone.
 */
function buildNatalChart(PDO $pdo, string $date, string $time, array $place): array
{
    $local = new DateTime("$date $time", new DateTimeZone($place['timezone']));
    $utc = (clone $local)->setTimezone(new DateTimeZone('UTC'));
    $jd = julianDay($utc);

    $points = [
        'sun' => sunLongitude($jd),
        'moon' => moonLongitude($jd),
        'asc' => ascendant($jd, (float)$place['latitude'], (float)$place['longitude']),
    ];
    $signs = allSigns($pdo);
    $result = ['utc' => $utc->format('d.m.Y H:i'), 'jd' => round($jd, 4)];
    foreach ($points as $key => $lonDeg) {
        $index = (int)floor($lonDeg / 30);
        $result[$key] = [
            'longitude' => round($lonDeg, 2),
            'degree' => round(fmod($lonDeg, 30), 2),
            'sign' => $signs[$index],
        ];
    }
    return $result;
}

function degreeText(float $deg): string
{
    $d = (int)floor($deg);
    $m = (int)round(($deg - $d) * 60);
    if ($m === 60) { $d++; $m = 0; }
    return $d . '°' . str_pad((string)$m, 2, '0', STR_PAD_LEFT) . "'";
}

// Краткие трактовки для личного кабинета и персонального гороскопа
function chartMeaning(string $point, array $sign): string
{
    $el = [
        'Огонь' => ['энергию, смелость и стремление действовать', 'вспыльчивые, но быстро отходящие эмоции', 'уверенным и активным'],
        'Земля' => ['практичность, надёжность и терпение', 'потребность в стабильности и предсказуемости', 'спокойным и основательным'],
        'Воздух' => ['любознательность, общительность и гибкость ума', 'потребность в общении и новых впечатлениях', 'лёгким и дружелюбным'],
        'Вода' => ['чуткость, интуицию и глубину чувств', 'тонкую эмоциональность и сопереживание', 'мягким и загадочным'],
    ][$sign['element']];
    switch ($point) {
        case 'sun':
            return "Солнце в знаке «{$sign['name']}» определяет основу характера: {$el[0]}. Управитель знака — {$sign['planet']}.";
        case 'moon':
            return "Луна в знаке «{$sign['name']}» описывает эмоциональный мир: {$el[1]}.";
        default:
            return "Асцендент в знаке «{$sign['name']}» показывает, каким вас видят окружающие: {$el[2]} человеком.";
    }
}
