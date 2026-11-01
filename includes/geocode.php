<?php
// Работа с внешним API: Open-Meteo Geocoding API (https://open-meteo.com/en/docs/geocoding-api).
// API бесплатное, ключ не требуется. По названию города возвращает координаты и часовой пояс,
// которые нужны для расчёта асцендента в натальной карте.

const GEOCODING_URL = 'https://geocoding-api.open-meteo.com/v1/search';

/**
 * Поиск места рождения.
 * Возвращает массив [name, country, region, latitude, longitude, timezone] или null, если город не найден.
 * В $error записывается текст ошибки при недоступности сервиса.
 */
function geocodePlace(string $city, ?string &$error = null): ?array
{
    $query = http_build_query([
        'name' => $city,
        'count' => 1,
        'language' => 'ru',
        'format' => 'json',
    ]);

    $ch = curl_init(GEOCODING_URL . '?' . $query);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'Asteria/1.0 (study project)',
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $status !== 200) {
        $error = 'Сервис геокодирования недоступен' . ($curlError ? ": $curlError" : " (код ответа $status)");
        return null;
    }

    $data = json_decode($response, true);
    if (empty($data['results'][0])) {
        $error = 'Город «' . $city . '» не найден. Проверьте написание.';
        return null;
    }

    $r = $data['results'][0];
    return [
        'name' => $r['name'],
        'country' => $r['country'] ?? '',
        'region' => $r['admin1'] ?? '',
        'latitude' => (float)$r['latitude'],
        'longitude' => (float)$r['longitude'],
        'timezone' => $r['timezone'] ?? 'Europe/Moscow',
        'source' => 'Open-Meteo Geocoding API',
    ];
}
