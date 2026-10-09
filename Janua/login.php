<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!authCsrfIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $error = 'Your session expired. Refresh this page and try again.';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address and password.';
        } else {
            $statement = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1');
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if (!$user || !password_verify($password, (string) $user['password_hash'])) {
                $error = 'That email and password do not match.';
            } else {
                if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
                    $updateHash = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
                    $updateHash->execute([
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => (int) $user['id'],
                    ]);
                }

                signInUser($pdo, $user);
                header('Location: index.php');
                exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071321">
    <title>Log in — JanuaFilm</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="ambient ambient-one" aria-hidden="true"></div>
    <div class="ambient ambient-two" aria-hidden="true"></div>
    <header class="auth-topbar">
        <a class="brand" href="index.php" aria-label="JanuaFilm home"><span class="brand-mark">J</span>Janua<span>Film</span></a>
        <a class="auth-back-link" href="index.php">← Back to movies</a>
    </header>
    <main class="auth-main">
        <section class="auth-card" aria-labelledby="auth-title">
            <div class="auth-card-mark">J</div>
            <p class="eyebrow auth-eyebrow"><span class="live-dot"></span> YOUR MOVIE NIGHT STARTS HERE</p>
            <h1 id="auth-title">Welcome back.</h1>
            <p class="auth-intro">Log in to pick up where your next favorite story begins.</p>
            <?php if ($error !== ''): ?><p class="auth-error" role="alert"><?= authEscape($error) ?></p><?php endif; ?>
            <form class="auth-form" method="post" action="login.php">
                <input type="hidden" name="csrf_token" value="<?= authEscape(authCsrfToken()) ?>">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="<?= authEscape($email) ?>" autocomplete="email" maxlength="254" required>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button class="button button-primary auth-submit" type="submit">Log in <span aria-hidden="true">→</span></button>
            </form>
            <p class="auth-switch">New to JanuaFilm? <a href="register.php">Create an account</a></p>
        </section>
    </main>
</body>
</html>
