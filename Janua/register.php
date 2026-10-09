<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$name = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!authCsrfIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $error = 'Your session expired. Refresh this page and try again.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($name === '' || strlen($name) > 120) {
            $error = 'Enter a name that is no longer than 120 characters.';
        } elseif (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif (strlen($password) < 8 || strlen($password) > 72) {
            $error = 'Choose a password between 8 and 72 characters.';
        } elseif ($password !== $passwordConfirmation) {
            $error = 'The passwords do not match.';
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash)
                 VALUES (:name, :email, :password_hash)'
            );
            try {
                $statement->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $user = [
                    'id' => (int) $pdo->lastInsertId(),
                    'name' => $name,
                ];
                signInUser($pdo, $user);
                header('Location: index.php');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $error = 'An account with that email already exists. Try logging in instead.';
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
    <title>Create an account — JanuaFilm</title>
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
            <p class="eyebrow auth-eyebrow"><span class="live-dot"></span> YOUR FRONT ROW SEAT</p>
            <h1 id="auth-title">Join the story.</h1>
            <p class="auth-intro">Create an account to save movies and make JanuaFilm yours.</p>
            <?php if ($error !== ''): ?><p class="auth-error" role="alert"><?= authEscape($error) ?></p><?php endif; ?>
            <form class="auth-form" method="post" action="register.php">
                <input type="hidden" name="csrf_token" value="<?= authEscape(authCsrfToken()) ?>">
                <label for="name">Your name</label>
                <input id="name" name="name" type="text" value="<?= authEscape($name) ?>" autocomplete="name" maxlength="120" required>
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="<?= authEscape($email) ?>" autocomplete="email" maxlength="254" required>
                <label for="password">Password <span class="field-hint">8–72 characters</span></label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                <label for="password-confirmation">Confirm password</label>
                <input id="password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                <button class="button button-primary auth-submit" type="submit">Create account <span aria-hidden="true">→</span></button>
            </form>
            <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
        </section>
    </main>
</body>
</html>
