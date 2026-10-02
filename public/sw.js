const CACHE_NAME = 'blasti-v1';
const STATIC_ASSETS = [
    '/',
    '/assets/css/style.css',
    '/assets/js/script.js',
    '/manifest.json'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch(() => {});
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Only handle GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Ticket or verification pages: Network-first, fallback to cache (offline ticket viewing)
    if (url.pathname.includes('/billet/') || url.pathname.includes('/reservation/') || url.pathname.includes('/ticket/')) {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    if (response && response.status === 200) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                    }
                    return response;
                })
                .catch(() => {
                    return caches.match(event.request);
                })
        );
        return;
    }

    // Static assets: Cache-first / Stale-while-revalidate
    if (url.pathname.startsWith('/assets/') || url.pathname.endsWith('.css') || url.pathname.endsWith('.js') || url.pathname.endsWith('.png') || url.pathname.endsWith('.jpg') || url.pathname.endsWith('.woff2')) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                const fetchPromise = fetch(event.request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, networkResponse.clone()));
                    }
                    return networkResponse;
                }).catch(() => cached);

                return cached || fetchPromise;
            })
        );
    }
});
