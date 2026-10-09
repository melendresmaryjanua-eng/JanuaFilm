# JanuaFilm

JanuaFilm is a PHP movie catalog for XAMPP. Accounts, movie details, and each signed-in user's My List are stored in MySQL. Passwords are stored as hashes.

## Run locally with XAMPP

1. Copy this project folder into `C:\xampp\htdocs\Janua`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. For a new installation, open `http://localhost/phpmyadmin`, choose **Import**, select `database.sql`, and run it. This creates the `blueflix` database, account table, watchlist table, and movie catalog.
   - If you already imported the old BlueFlix database, import `database-upgrade.sql` once first to add accounts and link saved movies to users. Then import `database-movies-update.sql` to add the current posters and feature *10 Things I Hate About You*.
4. If your MySQL login is not the XAMPP default, update the connection values at the top of `config.php`.
5. Open `http://localhost/Janua/`.

Register or log in from the site header. My List is saved to the signed-in account. Movie poster filenames in `Pics` match the artwork and displayed titles. The featured poster has a gentle animated zoom (disabled when reduced motion is requested). No video files are included, so movie buttons open a details preview rather than stream a film.
