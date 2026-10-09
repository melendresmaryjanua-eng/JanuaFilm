<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isSecure,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/config.php';

function authCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function authCsrfIsValid(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals((string) $_SESSION['csrf_token'], $token);
}

function authEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function signInUser(PDO $pdo, array $user): void
{
    $visitorToken = $_SESSION['visitor_token'] ?? null;
    if (is_string($visitorToken)) {
        $pdo->beginTransaction();
        $transferList = $pdo->prepare(
            'UPDATE watchlist SET user_id = :user_id, visitor_token = NULL
             WHERE visitor_token = :visitor_token AND user_id IS NULL'
        );
        $transferList->execute([
            'user_id' => (int) $user['id'],
            'visitor_token' => $visitorToken,
        ]);
        $pdo->commit();
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = (string) $user['name'];
    $_SESSION['visitor_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
