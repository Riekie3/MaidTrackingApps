// Minimal, dependency-free JS: "select all" checkboxes on approval
// queues, and toggling the reject-reason field.

document.addEventListener('change', function (e) {
    if (e.target.matches('[data-select-all]')) {
        var group = e.target.getAttribute('data-select-all');
        document.querySelectorAll('input[type=checkbox][data-group="' + group + '"]').forEach(function (cb) {
            cb.checked = e.target.checked;
        });
    }

    if (e.target.matches('[data-bulk-action]')) {
        var reasonBox = document.querySelector('[data-reason-for="' + e.target.getAttribute('data-bulk-action') + '"]');
        if (reasonBox) {
            reasonBox.style.display = e.target.value === 'reject' ? 'block' : 'none';
        }
    }
});

document.addEventListener('submit', function (e) {
    if (e.target.matches('[data-confirm]')) {
        if (!confirm(e.target.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    }
});

// --- Theme toggle -------------------------------------------------------
// header.php already applies any saved preference before first paint;
// this just handles the click and persists the choice.

var THEME_KEY = 'maidtrack-theme';

function currentTheme() {
    var explicit = document.documentElement.getAttribute('data-theme');
    if (explicit) return explicit;
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-theme-toggle]');
    if (!btn) return;
    var next = currentTheme() === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem(THEME_KEY, next); } catch (err) {}
});

// --- PWA: register the service worker ------------------------------------

if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register(document.body.getAttribute('data-sw-url') || '/sw.js').catch(function () {
            // Offline shell just won't be available — the app still works online.
        });
    });
}
