<?php
/**
 * Подключение к базе данных через PDO.
 * Файл возвращает singleton-соединение, доступное как $pdo.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>Нет соединения с базой</title>';
    echo '<div style="font:16px/1.6 system-ui;max-width:720px;margin:60px auto;padding:24px;'
        . 'border:1px solid #f0c9c9;border-radius:14px;background:#fff7f7">';
    echo '<h1 style="margin-top:0;font-size:22px">Не удалось подключиться к базе данных</h1>';
    echo '<p>Проверьте, что MySQL запущен в панели XAMPP, а база <b>' . htmlspecialchars(DB_NAME)
        . '</b> создана из файла <code>sql/schema.sql</code>.</p>';
    if (DEBUG) {
        echo '<pre style="white-space:pre-wrap;color:#a33">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
    echo '</div>';
    exit;
}

require_once __DIR__ . '/functions.php';
