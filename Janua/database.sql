CREATE DATABASE IF NOT EXISTS blueflix
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS movies (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    genre VARCHAR(60) NOT NULL,
    release_year SMALLINT UNSIGNED NOT NULL,
    rating DECIMAL(3,1) NOT NULL DEFAULT 8.0,
    duration VARCHAR(30) NOT NULL,
    poster VARCHAR(255) NOT NULL,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_movie_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS watchlist (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    visitor_token CHAR(64) NULL,
    movie_id INT UNSIGNED NOT NULL,
    added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_visitor_movie (visitor_token, movie_id),
    UNIQUE KEY unique_user_movie (user_id, movie_id),
    KEY index_watchlist_user (user_id),
    CONSTRAINT fk_watchlist_movie
        FOREIGN KEY (movie_id) REFERENCES movies (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_watchlist_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE,
    KEY index_watchlist_visitor (visitor_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO movies (title, description, genre, release_year, rating, duration, poster, featured) VALUES
('10 Things I Hate About You', 'Kat Stratford has no interest in dating, but when her younger sister is told she can only date if Kat does too, a clever plan brings unexpected sparks.', 'Romance', 1999, 7.3, '1h 37m', 'Pics/10 Things I Hate About You.jpg', 1),
('How to Lose a Guy in 10 Days', 'A magazine writer sets out to make a man leave her in ten days, while he bets he can make a woman fall in love with him in the same amount of time.', 'Romance', 2003, 6.5, '1h 56m', 'Pics/How to Lose a Guy in 10 Days.jpg', 0),
('The Proposal', 'A demanding editor persuades her assistant to pretend to marry her so she can stay in the country, leading to an unexpected family weekend in Alaska.', 'Romance', 2009, 6.8, '1h 48m', 'Pics/The proposal.jpg', 0),
('To All the Boys: P.S. I Still Love You', 'Lara Jean and Peter are officially a couple, but an old love letter brings another connection into her life and puts her feelings to the test.', 'Romance', 2020, 6.1, '1h 41m', 'Pics/To All the Boys P.S. I Still Love You.webp', 0),
('The Amazing Spider-Man', 'A curious teenager follows the clues left by his parents and discovers new powers, a new responsibility, and a dangerous secret in New York.', 'Action', 2012, 7.0, '2h 16m', 'Pics/The Amazing Spider-Man.webp', 0),
('Sterling Point', 'Two young women arrive at a quiet seaside town where old friendships, new beginnings, and buried secrets change everything.', 'Drama', 2025, 7.8, 'Limited series', 'Pics/Sterling Point.jpg', 0)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    genre = VALUES(genre),
    release_year = VALUES(release_year),
    rating = VALUES(rating),
    duration = VALUES(duration),
    poster = VALUES(poster),
    featured = VALUES(featured);
