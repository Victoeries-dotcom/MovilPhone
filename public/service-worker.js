/*
 * Mantiene disponibles los recursos visuales basicos y una pantalla offline.
 * Se conecta con manifest.webmanifest y layout.blade.php sin almacenar respuestas privadas.
 */
const CACHE_NAME = 'movilphone-shell-20260720';
const STATIC_RESOURCES = [
    '/offline',
    '/css/movilphone-ui.css?v=20260720-professional-suite',
    '/js/movilphone-ui.js?v=20260720-professional-suite',
    '/js/lucide.min.js?v=1.25.0',
    '/favicon.svg',
];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_RESOURCES)));
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    // Elimina versiones antiguas para que el tema y JavaScript se actualicen juntos.
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const request = event.request;

    // Las paginas autenticadas siempre consultan la red para no cachear datos de clientes o caja.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline')));
        return;
    }

    if (request.method === 'GET' && new URL(request.url).origin === self.location.origin) {
        event.respondWith(caches.match(request).then(cached => cached || fetch(request)));
    }
});
