// MaidTrack service worker — app-shell caching only. This app is mostly
// session-authenticated PHP pages, so we deliberately do NOT cache pages
// or ever touch POST requests; only the static shell (CSS/JS/icons) is
// cached, and navigations fall back to a static offline page if the
// network is down. That's enough to make the site installable as a PWA
// without risking stale or cross-account content ever being served.

const CACHE_NAME = 'maidtrack-shell-v1';
const PRECACHE_URLS = [
    './assets/css/app.css',
    './assets/js/app.js',
    './assets/icons/icon-192.png',
    './assets/icons/icon-512.png',
    './offline.html',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return; // never intercept form submissions

    const url = new URL(req.url);

    if (url.pathname.indexOf('/assets/') !== -1) {
        event.respondWith(
            caches.match(req).then((cached) => cached || fetch(req).then((res) => {
                const clone = res.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
                return res;
            }))
        );
        return;
    }

    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => caches.match('./offline.html'))
        );
    }
});
