const CACHE_NAME = 'srmh-static-v1';
const STATIC_ASSETS = [
    '/css/srmh.css',
    '/js/srmh-dashboard.js',
    '/js/srmh-modal.js',
    '/js/srmh-notifications.js',
    '/js/srmh-echo.js',
    '/js/srmh-push.js',
    '/js/srmh-analytics.js',
    '/js/srmh-report.js',
    '/icons/icon-192.png',
    '/icons/badge-72.png',
    '/manifest.webmanifest',
    '/offline.html',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET') return;
    if (url.pathname.startsWith('/app/') || url.pathname.startsWith('/apps')) return;
    if (url.pathname.startsWith('/api/') || url.pathname.includes('/broadcasting/')) return;

    if (STATIC_ASSETS.some((asset) => url.pathname === asset) || url.pathname.startsWith('/js/vendor/')) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                const clone = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                return response;
            }))
        );
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html'))
        );
    }
});

self.addEventListener('push', (event) => {
    let data = { title: 'SRMH', body: '', url: '/dashboard' };
    try {
        if (event.data) data = { ...data, ...event.data.json() };
    } catch (e) {
        //
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: '/icons/icon-192.png',
            badge: '/icons/badge-72.png',
            data: { url: data.url || '/dashboard' },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/dashboard';
    event.waitUntil(clients.openWindow(url));
});
