import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ─── Connection-budget-aware polling ───────────────────────────────────────
// Every poll is a PHP request and a MySQL connection, and shared hosting allows
// 500 per hour for the whole site; going over takes the portal down with a 500.
// So polling is deliberately lazy:
//   • never while the tab is hidden — it catches up as soon as it's visible again;
//   • never once the person has been idle (no mouse/keys/touch) for 15 minutes —
//     a dashboard left open on a desk overnight costs nothing — and it resumes
//     the moment they're back;
//   • the interval can be a function, so a caller can poll quickly while
//     something is happening and slowly otherwise (see the chat).
const IDLE_AFTER_MS = 15 * 60 * 1000;
let lastInteraction = Date.now();
const wakers = new Set();

['pointerdown', 'keydown', 'wheel', 'touchstart', 'mousemove'].forEach((event) =>
    window.addEventListener(event, () => {
        const wasIdle = Date.now() - lastInteraction > IDLE_AFTER_MS;
        lastInteraction = Date.now();
        if (wasIdle) wakers.forEach((wake) => wake());
    }, { passive: true }),
);

const isIdle = () => Date.now() - lastInteraction > IDLE_AFTER_MS;

window.pollWhileVisible = (callback, interval) => {
    const delay = () => (typeof interval === 'function' ? interval() : interval);
    let lastRun = Date.now();
    let timer = null;
    let running = false;

    const run = async () => {
        if (running) return;
        running = true;
        lastRun = Date.now();
        try {
            await callback();
        } finally {
            running = false;
        }
    };

    const schedule = () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            if (!document.hidden && !isIdle()) await run();
            schedule();
        }, delay());
    };

    const catchUp = () => {
        if (!document.hidden && !isIdle() && Date.now() - lastRun >= delay()) {
            run().then(schedule);
        }
    };

    document.addEventListener('visibilitychange', catchUp);
    wakers.add(catchUp);
    schedule();

    // Lets a caller refresh right now (e.g. a "Refresh" button) and restart the clock.
    return { now: () => run().then(schedule) };
};

// ─── Chat formatting helpers ───────────────────────────────────────────────
window.chatFormat = {
    time(iso) {
        return new Date(iso).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    },

    full(iso) {
        return new Date(iso).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
    },

    day(iso) {
        const date = new Date(iso);
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);

        if (date.toDateString() === today.toDateString()) return 'Today';
        if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';

        return date.toLocaleDateString([], {
            weekday: 'short', month: 'short', day: 'numeric',
            year: date.getFullYear() === today.getFullYear() ? undefined : 'numeric',
        });
    },

    // Escapes first, then turns bare URLs into links — the only HTML a message
    // can ever contain is the <a> tags added here.
    linkify(text) {
        const escaped = String(text)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

        return escaped.replace(/\bhttps?:\/\/[^\s<]+[^\s<.,;:!?)\]'"]/g,
            (url) => `<a href="${url}" target="_blank" rel="noopener noreferrer nofollow" class="underline underline-offset-2 break-all">${url}</a>`);
    },
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
