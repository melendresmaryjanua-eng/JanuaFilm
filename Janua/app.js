(() => {
    const dataElement = document.getElementById('movie-data');
    const dialog = document.querySelector('.movie-dialog');
    const toast = document.querySelector('.toast');

    if (!dataElement || !dialog) return;

    let movies;
    try {
        movies = JSON.parse(dataElement.textContent || '[]');
    } catch (error) {
        console.error('Could not load movie details.', error);
        return;
    }

    const poster = dialog.querySelector('.dialog-poster');
    const title = dialog.querySelector('#dialog-title');
    const meta = dialog.querySelector('.dialog-meta');
    const description = dialog.querySelector('.dialog-description');

    document.querySelectorAll('[data-open-movie]').forEach((button) => {
        button.addEventListener('click', () => {
            const movie = movies.find((item) => item.id === Number(button.dataset.openMovie));
            if (!movie) return;

            poster.src = movie.poster;
            poster.alt = `Poster for ${movie.title}`;
            title.textContent = movie.title;
            description.textContent = movie.description;

            const year = document.createElement('span');
            year.textContent = String(movie.year);
            const duration = document.createElement('span');
            duration.textContent = movie.duration;
            const genre = document.createElement('span');
            genre.textContent = movie.genre;
            const rating = document.createElement('strong');
            rating.textContent = `★ ${movie.rating}`;
            meta.replaceChildren(year, duration, genre, rating);

            dialog.showModal();
        });
    });

    dialog.querySelector('.dialog-close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });

    if (toast) {
        const status = document.querySelector('.flash-message');
        if (status) {
            toast.textContent = status.textContent;
            toast.classList.add('show');
            window.setTimeout(() => toast.classList.remove('show'), 2800);
        }
    }
})();
