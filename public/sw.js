/* ВелоТОР — service worker для PWA.
 *
 * Задачи:
 *  - делает сайт устанавливаемым (наличие fetch-обработчика — требование браузера);
 *  - базовый офлайн: статику кэшируем, страницы отдаём network-first с фолбэком.
 *
 * Осознанно НЕ трогаем: не-GET запросы, чужие домены, /admin, вебхук Telegram и
 * XHR Inertia (заголовок X-Inertia) — они всегда идут напрямую в сеть, чтобы не
 * ломать навигацию, формы и админку.
 */

const CACHE = 'velotor-v1';
const APP_SHELL = [
    '/',
    '/site.webmanifest',
    '/images/logo.png',
    '/images/pwa/icon-192.png',
    '/images/pwa/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE)
            .then((cache) => cache.addAll(APP_SHELL))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

const STATIC_RE = /\.(?:css|js|mjs|png|jpe?g|gif|svg|webp|ico|woff2?|ttf)$/i;

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Только свой домен; чужое (шрифты, внешние ссылки) — мимо кэша.
    if (url.origin !== self.location.origin) {
        return;
    }

    // Динамика, которую нельзя кэшировать «намертво».
    if (
        url.pathname.startsWith('/admin')
        || url.pathname.startsWith('/telegram')
        || request.headers.get('X-Inertia')
    ) {
        return;
    }

    // Полные переходы по страницам: сеть, при офлайне — кэшированная главная.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/').then((r) => r || Response.error()))
        );
        return;
    }

    // Статика: сначала кэш, параллельно тихо обновляем из сети.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/images/') || STATIC_RE.test(url.pathname)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const fromNetwork = fetch(request)
                    .then((resp) => {
                        if (resp && resp.ok) {
                            const copy = resp.clone();
                            caches.open(CACHE).then((cache) => cache.put(request, copy));
                        }
                        return resp;
                    })
                    .catch(() => cached);

                return cached || fromNetwork;
            })
        );
    }
});
