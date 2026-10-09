<?php
declare(strict_types=1);

$databaseHost = 'localhost';
$databaseName = 'blueflix';
$databaseUser = 'root';
$databasePassword = '';

try {
    $pdo = new PDO(
        "mysql:host={$databaseHost};dbname={$databaseName};charset=utf8mb4",
        $databaseUser,
        $databasePassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(503);
    $setupMessage = 'Could not connect to MySQL. Import database.sql in phpMyAdmin and check the database settings in config.php.';
    if (ini_get('display_errors')) {
        $setupMessage .= ' Database error: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
    }
    exit(
        '<!doctype html><html lang="en"><meta charset="utf-8"><title>JanuaFilm setup</title>' .
        '<body style="background:#071323;color:#e8f2ff;font:16px Arial;padding:48px">' .
        '<h1>JanuaFilm needs its database</h1><p>' . $setupMessage . '</p></body></html>'
    );
}
