<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
if (!authCsrfIsValid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid request token. Refresh the page and try again.');
}

$_SESSION = [];
session_regenerate_id(true);
$_SESSION['visitor_token'] = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['flash_message'] = 'You are now logged out.';

header('Location: index.php');
exit;
