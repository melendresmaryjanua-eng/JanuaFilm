USE blueflix;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE watchlist
    MODIFY visitor_token CHAR(64) NULL,
    ADD COLUMN user_id INT UNSIGNED NULL AFTER id,
    ADD UNIQUE KEY unique_user_movie (user_id, movie_id),
    ADD KEY index_watchlist_user (user_id),
    ADD CONSTRAINT fk_watchlist_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE;

UPDATE movies SET poster = 'Pics/Spider-Man No Way Home.jpg'
WHERE title = 'Spider-Man: No Way Home';
UPDATE movies SET poster = 'Pics/The Hunger Games Mockingjay.webp'
WHERE title = 'The Hunger Games: Mockingjay - Part 1';
UPDATE movies SET poster = 'Pics/Hunger Games The ballad of Songbird & Snakes.jpg'
WHERE title = 'The Ballad of Songbirds & Snakes';
UPDATE movies SET poster = 'Pics/The Amazing Spider-Man.webp'
WHERE title = 'The Amazing Spider-Man';
UPDATE movies SET poster = 'Pics/Sterling Point.jpg'
WHERE title = 'Sterling Point';
UPDATE movies
SET title = 'The Hunger Games: The Ballad of Songbirds & Snakes'
WHERE title = 'The Ballad of Songbirds & Snakes';
