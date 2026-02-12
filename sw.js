const CACHE_NAME = 'jmc-foodies-v1';
const OFFLINE_URL = '/Icecream/offline.html';

// Assets to pre-cache
const PRE_CACHE = [
    OFFLINE_URL,
    '/Icecream/assets/css/custom.css',
    '/Icecream/assets/js/custom.js',
    '/Icecream/manifest.json'
];

// Install: pre-cache essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRE_CACHE);
        })
    );
    self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

// Fetch: network-first strategy for PHP pages, cache-first for static assets
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Skip API calls (cart operations need real-time data)
    if (url.pathname.includes('/api/')) return;

    // Static assets (CSS, JS, fonts, images): cache-first
    if (url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|ico|woff2?|ttf|svg)$/)) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                if (cached) return cached;
                return fetch(event.request).then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // PHP pages: network-first, fallback to offline page
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Cache successful page responses
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => {
                // Try cached version first, then offline page
                return caches.match(event.request).then((cached) => {
                    return cached || caches.match(OFFLINE_URL);
                });
            })
    );
});
