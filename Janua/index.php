<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

if (!isset($_SESSION['visitor_token'])) {
    $_SESSION['visitor_token'] = bin2hex(random_bytes(32));
}
$csrfToken = authCsrfToken();
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$userName = isset($_SESSION['user_name']) ? (string) $_SESSION['user_name'] : '';
$flashMessage = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
$watchlistView = isset($_GET['watchlist']);

if ($watchlistView && $userId === null) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!authCsrfIsValid($postedToken)) {
        http_response_code(403);
        exit('Invalid request token. Refresh the page and try again.');
    }
    if ($userId === null) {
        header('Location: login.php');
        exit;
    }

    $movieId = filter_input(INPUT_POST, 'movie_id', FILTER_VALIDATE_INT);
    if (!$movieId || $movieId < 1) {
        http_response_code(400);
        exit('A valid movie is required.');
    }

    $movieExists = $pdo->prepare('SELECT id FROM movies WHERE id = :id');
    $movieExists->execute(['id' => $movieId]);
    if (!$movieExists->fetch()) {
        http_response_code(404);
        exit('That movie could not be found.');
    }

    $existingItem = $pdo->prepare(
        'SELECT id FROM watchlist WHERE user_id = :user_id AND movie_id = :movie_id'
    );
    $existingItem->execute(['user_id' => $userId, 'movie_id' => $movieId]);
    if ($existingItem->fetch()) {
        $removeItem = $pdo->prepare(
            'DELETE FROM watchlist WHERE user_id = :user_id AND movie_id = :movie_id'
        );
        $removeItem->execute(['user_id' => $userId, 'movie_id' => $movieId]);
        $_SESSION['flash_message'] = 'Removed from My List.';
    } else {
        $addItem = $pdo->prepare(
            'INSERT INTO watchlist (user_id, movie_id) VALUES (:user_id, :movie_id)'
        );
        $addItem->execute(['user_id' => $userId, 'movie_id' => $movieId]);
        $_SESSION['flash_message'] = 'Added to My List.';
    }

    $redirectTo = 'index.php';
    if (!empty($_POST['watchlist_view'])) {
        $redirectTo .= '?watchlist=1';
    } elseif (!empty($_POST['search_value'])) {
        $redirectTo .= '?q=' . rawurlencode((string) $_POST['search_value']);
    } elseif (!empty($_POST['genre_value'])) {
        $redirectTo .= '?genre=' . rawurlencode((string) $_POST['genre_value']);
    }
    header('Location: ' . $redirectTo);
    exit;
}

$search = trim((string) ($_GET['q'] ?? ''));
$genre = trim((string) ($_GET['genre'] ?? ''));
$genreRows = $pdo->query('SELECT DISTINCT genre, poster FROM movies ORDER BY genre')->fetchAll();
$genres = [];
foreach ($genreRows as $genreRow) {
    $posterPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $genreRow['poster']);
    if (is_file($posterPath) && !in_array($genreRow['genre'], $genres, true)) {
        $genres[] = (string) $genreRow['genre'];
    }
}
$featuredStatement = $pdo->prepare(
    'SELECT m.*, EXISTS(
        SELECT 1 FROM watchlist w WHERE w.movie_id = m.id AND w.user_id = :user_id
    ) AS in_watchlist FROM movies m WHERE m.featured = 1 ORDER BY m.id LIMIT 1'
);
$featuredStatement->execute(['user_id' => $userId ?? 0]);
$featuredMovie = $featuredStatement->fetch();
if (!$featuredMovie || !is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $featuredMovie['poster']))) {
    $featuredStatement = $pdo->prepare(
        'SELECT m.*, EXISTS(
            SELECT 1 FROM watchlist w WHERE w.movie_id = m.id AND w.user_id = :user_id
        ) AS in_watchlist FROM movies m ORDER BY m.featured DESC, m.id'
    );
    $featuredStatement->execute(['user_id' => $userId ?? 0]);
    $featuredMovie = false;
    while ($candidate = $featuredStatement->fetch()) {
        $posterPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $candidate['poster']);
        if (is_file($posterPath)) {
            $featuredMovie = $candidate;
            break;
        }
    }
}

$sql = 'SELECT m.*, EXISTS(
    SELECT 1 FROM watchlist w WHERE w.movie_id = m.id AND w.user_id = :user_id
) AS in_watchlist FROM movies m';
$conditions = [];
$parameters = ['user_id' => $userId ?? 0];
if ($watchlistView) {
    $conditions[] = 'EXISTS(SELECT 1 FROM watchlist w WHERE w.movie_id = m.id AND w.user_id = :list_user_id)';
    $parameters['list_user_id'] = $userId;
}
if ($search !== '') {
    $conditions[] = '(m.title LIKE :search_title OR m.description LIKE :search_description OR m.genre LIKE :search_genre)';
    $searchTerm = '%' . $search . '%';
    $parameters['search_title'] = $searchTerm;
    $parameters['search_description'] = $searchTerm;
    $parameters['search_genre'] = $searchTerm;
}
if ($genre !== '' && in_array($genre, $genres, true)) {
    $conditions[] = 'm.genre = :genre';
    $parameters['genre'] = $genre;
}
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY m.featured DESC, m.release_year DESC, m.title';
$movieStatement = $pdo->prepare($sql);
$movieStatement->execute($parameters);
$movies = array_values(array_filter(
    $movieStatement->fetchAll(),
    static function (array $movie): bool {
        $posterPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $movie['poster']);
        return is_file($posterPath);
    }
));

$countStatement = $pdo->prepare(
    'SELECT poster FROM watchlist w JOIN movies m ON m.id = w.movie_id WHERE w.user_id = :user_id'
);
$countStatement->execute(['user_id' => $userId ?? 0]);
$watchlistCount = count(array_filter(
    $countStatement->fetchAll(PDO::FETCH_COLUMN),
    static function (string $poster): bool {
        $posterPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $poster);
        return is_file($posterPath);
    }
));

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function posterUrl(string $path): string
{
    return implode('/', array_map('rawurlencode', explode('/', $path)));
}

$featuredPoster = $featuredMovie ? posterUrl((string) $featuredMovie['poster']) : '';
$activeGenre = $genre !== '' && in_array($genre, $genres, true) ? $genre : '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071321">
    <title>JanuaFilm — Stories worth staying up for</title>
    <link rel="stylesheet" href="style.css">
    <script src="app.js" defer></script>
</head>
<body>
<div class="ambient ambient-one" aria-hidden="true"></div>
<div class="ambient ambient-two" aria-hidden="true"></div>
<header class="topbar">
    <a class="brand" href="index.php" aria-label="JanuaFilm home"><span class="brand-mark">J</span>Janua<span>Film</span></a>
    <nav class="main-nav" aria-label="Main navigation">
        <a class="<?= $watchlistView ? '' : 'active' ?>" href="index.php">Discover</a>
        <a class="<?= $watchlistView ? 'active' : '' ?>" href="<?= $userId === null ? 'login.php' : 'index.php?watchlist=1' ?>">My List <span class="nav-count"><?= $watchlistCount ?></span></a>
    </nav>
    <form class="search-form" action="index.php" method="get" role="search">
        <?php if ($watchlistView): ?><input type="hidden" name="watchlist" value="1"><?php endif; ?>
        <label class="sr-only" for="movie-search">Search movies</label>
        <span class="search-icon" aria-hidden="true"></span>
        <input id="movie-search" type="search" name="q" value="<?= escape($search) ?>" placeholder="Search titles, genres..." autocomplete="off">
        <button type="submit" aria-label="Search">Search</button>
    </form>
    <?php if ($userId !== null): ?>
        <span class="account-name"><?= escape($userName) ?></span>
        <form class="inline-form" method="post" action="logout.php">
            <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
            <button class="account-action" type="submit">Log out</button>
        </form>
    <?php else: ?>
        <a class="account-action" href="login.php">Log in</a>
        <a class="account-action account-action-primary" href="register.php">Join</a>
    <?php endif; ?>
</header>

<main>
    <?php if ($featuredMovie && !$watchlistView && $search === '' && $activeGenre === ''): ?>
        <section class="hero <?= (string) $featuredMovie['title'] === '10 Things I Hate About You' ? 'hero-featured-romance' : '' ?>" style="--hero-image: url('<?= escape($featuredPoster) ?>')">
            <div class="hero-shade"></div>
            <div class="hero-content">
                <p class="eyebrow"><span class="live-dot"></span> YOUR NEXT FAVORITE STORY</p>
                <h1><?= escape((string) $featuredMovie['title']) ?></h1>
                <div class="hero-meta">
                    <span class="match">98% match</span><span><?= (int) $featuredMovie['release_year'] ?></span>
                    <span class="rating-badge">★ <?= number_format((float) $featuredMovie['rating'], 1) ?></span>
                    <span class="meta-pill"><?= escape((string) $featuredMovie['genre']) ?></span>
                </div>
                <p class="hero-description"><?= escape((string) $featuredMovie['description']) ?></p>
                <div class="hero-actions">
                    <button class="button button-primary" type="button" data-open-movie="<?= (int) $featuredMovie['id'] ?>">
                        <span class="play-icon" aria-hidden="true">▶</span> Explore movie
                    </button>
                    <?php if ($userId !== null): ?>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                            <input type="hidden" name="movie_id" value="<?= (int) $featuredMovie['id'] ?>">
                            <button class="button button-glass" type="submit"><?= !empty($featuredMovie['in_watchlist']) ? '✓ In My List' : '+ My List' ?></button>
                        </form>
                    <?php else: ?>
                        <a class="button button-glass" href="login.php">Log in to save</a>
                    <?php endif; ?>
                </div>
                <div class="hero-footnote"><span class="quality-badge">HD</span> A little wonder goes a long way.</div>
            </div>
            <div class="hero-side-label"><span>01</span> / <?= str_pad((string) count($movies), 2, '0', STR_PAD_LEFT) ?></div>
        </section>
    <?php else: ?>
        <section class="page-heading">
            <p class="eyebrow"><span class="live-dot"></span> THE JANUAFILM COLLECTION</p>
            <h1><?= $watchlistView ? 'Your list, your next story.' : ($search !== '' ? 'Search results' : 'Find your next favorite.') ?></h1>
            <p><?= $watchlistView ? 'All the good stuff you saved, together in one place.' : 'Big adventures, brilliant heroes, and stories that stay with you.' ?></p>
        </section>
    <?php endif; ?>

    <section class="catalog-section" id="collection">
        <div class="section-heading">
            <div>
                <p class="eyebrow section-eyebrow"><?= $watchlistView ? 'SAVED FOR LATER' : 'HAND-PICKED FOR YOU' ?></p>
                <h2><?= $watchlistView ? 'My List' : ($activeGenre !== '' ? escape($activeGenre) . ' stories' : ($search !== '' ? 'What we found' : 'The spotlight')) ?></h2>
            </div>
            <form class="genre-filter" action="index.php#collection" method="get">
                <?php if ($watchlistView): ?><input type="hidden" name="watchlist" value="1"><?php endif; ?>
                <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= escape($search) ?>"><?php endif; ?>
                <label class="sr-only" for="genre-filter">Filter by genre</label>
                <select id="genre-filter" name="genre" onchange="this.form.submit()">
                    <option value="">All genres</option>
                    <?php foreach ($genres as $genreOption): ?>
                        <option value="<?= escape((string) $genreOption) ?>" <?= $activeGenre === $genreOption ? 'selected' : '' ?>><?= escape((string) $genreOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($flashMessage !== ''): ?>
            <div class="flash-message" role="status"><?= escape((string) $flashMessage) ?></div>
        <?php endif; ?>

        <?php if ($movies): ?>
            <div class="movie-grid">
                <?php foreach ($movies as $movie): ?>
                    <article class="movie-card" style="--card-delay: <?= (int) array_search($movie, $movies, true) * 70 ?>ms">
                        <button class="poster-button" type="button" data-open-movie="<?= (int) $movie['id'] ?>" aria-label="Details for <?= escape((string) $movie['title']) ?>">
                            <img class="movie-poster" src="<?= escape(posterUrl((string) $movie['poster'])) ?>" alt="Poster for <?= escape((string) $movie['title']) ?>" loading="lazy">
                            <span class="poster-overlay"><span class="poster-play">▶</span><span>VIEW DETAILS</span></span>
                            <span class="poster-rating">★ <?= number_format((float) $movie['rating'], 1) ?></span>
                        </button>
                        <div class="movie-info">
                            <div class="movie-title-row">
                                <h3><?= escape((string) $movie['title']) ?></h3>
                                <span class="movie-year"><?= (int) $movie['release_year'] ?></span>
                            </div>
                            <p><?= escape((string) $movie['genre']) ?> <span class="meta-separator">·</span> <?= escape((string) $movie['duration']) ?></p>
                            <div class="card-bottom">
                                <span class="match-label"><span class="match-dot"></span> MADE FOR YOU</span>
                                <?php if ($userId !== null): ?>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                                        <input type="hidden" name="movie_id" value="<?= (int) $movie['id'] ?>">
                                        <?php if ($watchlistView): ?><input type="hidden" name="watchlist_view" value="1"><?php endif; ?>
                                        <?php if ($search !== ''): ?><input type="hidden" name="search_value" value="<?= escape($search) ?>"><?php endif; ?>
                                        <?php if ($activeGenre !== ''): ?><input type="hidden" name="genre_value" value="<?= escape($activeGenre) ?>"><?php endif; ?>
                                        <button class="list-button <?= !empty($movie['in_watchlist']) ? 'is-saved' : '' ?>" type="submit" aria-label="<?= !empty($movie['in_watchlist']) ? 'Remove from' : 'Add to' ?> My List"><?= !empty($movie['in_watchlist']) ? '✓' : '+' ?></button>
                                    </form>
                                <?php else: ?>
                                    <a class="list-button list-login-link" href="login.php" aria-label="Log in to add <?= escape((string) $movie['title']) ?> to My List">+</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <span class="empty-icon"><?= $watchlistView ? '♡' : '⌕' ?></span>
                <h3><?= $watchlistView ? 'Your list is ready for a story.' : 'No titles found this time.' ?></h3>
                <p><?= $watchlistView ? 'Tap the + on any title and it will be waiting here.' : 'Try another title or clear your genre filter.' ?></p>
                <a class="button button-glass" href="index.php">Back to discover</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="site-footer"><span class="brand brand-small"><span class="brand-mark">J</span>Janua<span>Film</span></span><span>Made for nights in and stories that take you somewhere.</span><a href="#top" onclick="window.scrollTo({top:0,behavior:'smooth'});return false">Back to top ↑</a></footer>

<dialog class="movie-dialog" aria-labelledby="dialog-title">
    <button class="dialog-close" type="button" aria-label="Close details">×</button>
    <div class="dialog-art"><img src="" alt="" class="dialog-poster"></div>
    <div class="dialog-content">
        <p class="eyebrow"><span class="live-dot"></span> JANUAFILM PICK</p>
        <h2 id="dialog-title"></h2>
        <div class="dialog-meta"></div>
        <p class="dialog-description"></p>
        <p class="dialog-note">This is a movie library preview. Add the title to My List to save it for later.</p>
    </div>
</dialog>
<div class="toast" role="status" aria-live="polite"></div>
<script type="application/json" id="movie-data"><?= json_encode(array_map(static function (array $movie): array {
    return [
        'id' => (int) $movie['id'],
        'title' => $movie['title'],
        'description' => $movie['description'],
        'genre' => $movie['genre'],
        'year' => (int) $movie['release_year'],
        'rating' => number_format((float) $movie['rating'], 1),
        'duration' => $movie['duration'],
        'poster' => posterUrl((string) $movie['poster']),
    ];
}, $movies), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?></script>
</body>
</html>
