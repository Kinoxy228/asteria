<?php
// Отрисовка круга натальной карты в формате SVG.
// Асцендент располагается слева (как принято в астрологии), знаки идут против часовой стрелки.

function zodiacPath(string $slug): string
{
    $svg = file_get_contents(__DIR__ . '/../assets/img/icons/zodiac-' . $slug . '.svg');
    preg_match('/ d="([^"]+)"/', $svg, $m);
    return $m[1] ?? '';
}

function natalWheel(array $chart, int $size = 420): string
{
    $c = $size / 2;
    $rOuter = $c - 6;
    $rSigns = $rOuter - 34;
    $rInner = $rSigns - 70;
    $asc = $chart['asc']['longitude'];
    // Экранный угол (в градусах, против часовой от оси X) для эклиптической долготы
    $ang = fn(float $lon) => deg2rad(180 + ($lon - $asc));
    $pt = fn(float $lon, float $r) => [$c + $r * cos($ang($lon)), $c - $r * sin($ang($lon))];

    $colors = ['Огонь' => '#F28C5B', 'Земля' => '#8FC48B', 'Воздух' => '#8FB6F2', 'Вода' => '#7FD3D8'];
    $signs = $GLOBALS['pdo']->query('SELECT slug, element FROM zodiac_signs ORDER BY id')->fetchAll();

    $out = '<svg class="wheel" viewBox="0 0 ' . $size . ' ' . $size . '" xmlns="http://www.w3.org/2000/svg">';
    $out .= '<circle cx="' . $c . '" cy="' . $c . '" r="' . $rOuter . '" fill="#171433" stroke="#E8C46A" stroke-width="1.5"/>';
    $out .= '<circle cx="' . $c . '" cy="' . $c . '" r="' . $rSigns . '" fill="#120F2A" stroke="#4B4385"/>';
    $out .= '<circle cx="' . $c . '" cy="' . $c . '" r="' . $rInner . '" fill="#0E0C22" stroke="#4B4385"/>';
    foreach ($signs as $i => $s) {
        $start = $i * 30;
        [$x1, $y1] = $pt($start, $rOuter);
        [$x2, $y2] = $pt($start, $rInner);
        $out .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#4B4385"/>', $x1, $y1, $x2, $y2);
        [$gx, $gy] = $pt($start + 15, ($rOuter + $rSigns) / 2);
        $out .= sprintf('<g transform="translate(%.1f %.1f) scale(0.9) translate(-12 -12)"><path d="%s" fill="%s"/></g>',
            $gx, $gy, zodiacPath($s['slug']), $colors[$s['element']]);
        // деления по 10°
        for ($d = 10; $d < 30; $d += 10) {
            [$a1, $b1] = $pt($start + $d, $rSigns);
            [$a2, $b2] = $pt($start + $d, $rSigns - 6);
            $out .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#4B4385"/>', $a1, $b1, $a2, $b2);
        }
    }
    // Ось асцендент — десцендент
    [$ax, $ay] = $pt($asc, $rOuter + 2);
    [$dx, $dy] = $pt($asc + 180, $rOuter + 2);
    $out .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#E8C46A" stroke-width="1.5" stroke-dasharray="4 4"/>', $ax, $ay, $dx, $dy);
    $out .= sprintf('<text x="%.1f" y="%.1f" fill="#E8C46A" font-size="13" font-family="Montserrat" font-weight="700">ASC</text>', $ax + 4, $ay - 6);

    $planets = [
        'sun' => ['☉', '#E8C46A', 'Солнце'],
        'moon' => ['☽', '#D9D4FF', 'Луна'],
    ];
    $radius = ['sun' => $rInner - 24, 'moon' => $rInner - 62];
    foreach ($planets as $key => [$glyph, $color, $title]) {
        $lon = $chart[$key]['longitude'];
        $r = $radius[$key];
        [$px, $py] = $pt($lon, $r);
        [$lx, $ly] = $pt($lon, $rSigns);
        [$lx2, $ly2] = $pt($lon, $rInner);
        $out .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="%s" stroke-width="2"/>', $lx, $ly, $lx2, $ly2, $color);
        $out .= sprintf('<circle cx="%.1f" cy="%.1f" r="17" fill="#1E1A44" stroke="%s" stroke-width="1.5"><title>%s</title></circle>', $px, $py, $color, $title);
        $out .= sprintf('<text x="%.1f" y="%.1f" fill="%s" font-size="20" text-anchor="middle" dominant-baseline="central" font-family="Segoe UI Symbol, DejaVu Sans, sans-serif">%s</text>', $px, $py + 1, $color, $glyph);
    }
    $out .= '<circle cx="' . $c . '" cy="' . $c . '" r="4" fill="#E8C46A"/>';
    return $out . '</svg>';
}
