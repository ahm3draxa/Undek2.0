import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Every poll is a PHP request and a fresh MySQL connection, and shared hosting
// caps connections per hour for the whole site — one forgotten background tab
// polling every few seconds used to exhaust it and take the public site down
// with a 500. So: never poll while the tab is hidden, and catch up immediately
// when it becomes visible again instead of waiting out the interval.
window.pollWhileVisible = (callback, intervalMs) => {
    let lastRun = Date.now();

    const run = () => {
        lastRun = Date.now();
        callback();
    };

    setInterval(() => {
        if (!document.hidden) run();
    }, intervalMs);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && Date.now() - lastRun >= intervalMs) run();
    });
};

Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),

    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
    },
});

Alpine.start();
