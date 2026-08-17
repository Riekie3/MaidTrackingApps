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
// Light is always the default — header.php only applies data-theme if
// the visitor has explicitly clicked the toggle before (saved in
// localStorage); system/OS dark-mode preference is never consulted.

var THEME_KEY = 'maidtrack-theme';

function currentTheme() {
    return document.documentElement.getAttribute('data-theme') || 'light';
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
