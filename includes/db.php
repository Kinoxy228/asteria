<?php
// Подключение к базе данных «asteria» через PDO.
// На локальном сервере XAMPP используется MySQL (MariaDB) с пользователем root без пароля.
$dbHost = 'localhost';
$dbName = 'asteria';
$dbUser = 'root';
$dbPass = '';

$dsn = getenv('ASTERIA_DSN') ?: "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
} catch (PDOException $e) {
    http_response_code(500);
    die('Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage()));
}

date_default_timezone_set('Europe/Moscow');
