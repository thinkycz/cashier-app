const CACHE_VERSION = 'cashier-offline-v2';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const DOC_CACHE = `${CACHE_VERSION}-documents`;
const API_CACHE = `${CACHE_VERSION}-api`;
const OFFLINE_FALLBACK = '/offline.html';
const DOCUMENT_FALLBACKS = ['/dashboard', '/'];

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(DOC_CACHE);

        try {
            await cache.addAll([...DOCUMENT_FALLBACKS, OFFLINE_FALLBACK]);
        } catch {
            // Installation should not fail if an authenticated route cannot be cached.
        }

        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.map((key) => {
            if (!key.startsWith(CACHE_VERSION)) {
                return caches.delete(key);
            }

            return Promise.resolve();
        }));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirst(request, API_CACHE));
        return;
    }

    if (request.mode === 'navigate' || request.destination === 'document') {
        event.respondWith(handleNavigationRequest(request));
        return;
    }

    if (['script', 'style', 'font', 'image'].includes(request.destination)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
    }
});

async function handleNavigationRequest(request) {
    try {
        const response = await fetch(request);
        const cache = await caches.open(DOC_CACHE);
        cache.put(request, response.clone());
        return response;
    } catch {
        const cache = await caches.open(DOC_CACHE);
        const directMatch = await cache.match(request, { ignoreSearch: true });
        if (directMatch) {
            return directMatch;
        }

        for (const fallbackPath of DOCUMENT_FALLBACKS) {
            const fallbackResponse = await cache.match(fallbackPath, { ignoreSearch: true });
            if (fallbackResponse) {
                return fallbackResponse;
            }
        }

        const offlineResponse = await cache.match(OFFLINE_FALLBACK, { ignoreSearch: true });
        if (offlineResponse) {
            return offlineResponse;
        }

        return new Response('Offline and no cached page is available yet.', {
            status: 503,
            statusText: 'Offline',
            headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        });
    }
}

async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    const networkResponse = await fetch(request);
    cache.put(request, networkResponse.clone());
    return networkResponse;
}

async function networkFirst(request, cacheName) {
    const cache = await caches.open(cacheName);

    try {
        const networkResponse = await fetch(request);
        cache.put(request, networkResponse.clone());
        return networkResponse;
    } catch {
        const cachedResponse = await cache.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        throw new Error('Network and cache both unavailable.');
    }
}

async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);

    const networkPromise = fetch(request)
        .then((networkResponse) => {
            cache.put(request, networkResponse.clone());
            return networkResponse;
        })
        .catch(() => null);

    if (cachedResponse) {
        return cachedResponse;
    }

    const networkResponse = await networkPromise;
    if (networkResponse) {
        return networkResponse;
    }

    throw new Error('Document unavailable offline.');
}
