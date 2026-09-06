const CACHE_NAME = 'profactory-v6';
const OFFLINE_URL = '/offline.html';

// Files to cache for offline use (Aggressive caching to fix Ngrok latency)
const STATIC_CACHE = [
    '/',
    '/dashboard',
    '/offline.html',
    '/manifest.json',
    '/icons/icon-72.png',
    '/icons/icon-96.png',
    '/icons/icon-128.png',
    '/icons/icon-192.png',
    '/icons/icon-384.png',
    '/icons/icon-512.png',
    '/libs/css/bootstrap.rtl.min.css',
    '/libs/css/bootstrap-icons.css',
    '/libs/css/tajawal.css',
    '/libs/css/tom-select.min.css',
    '/libs/fonts/tajawal-400.woff2',
    '/libs/fonts/tajawal-700.woff2',
    '/libs/fonts/bootstrap-icons.woff2',
    '/libs/js/chart.min.js',
    '/libs/js/turbo.js',
    '/build/assets/app-DzSsQL3u.css'
];

// Install: cache static assets
self.addEventListener('install', function(e) {
    e.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.addAll(STATIC_CACHE).catch(function(err) {
                console.log('Cache error:', err);
            });
        })
    );
    self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener('activate', function(e) {
    e.waitUntil(
        caches.keys().then(function(keys) {
            return Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            );
        })
    );
    return self.clients.claim();
});

// Fetch: Network first for HTML, Cache first for assets (Images, CSS, JS, Fonts)
self.addEventListener('fetch', function(e) {
    if (e.request.method !== 'GET') return;
    
    const url = new URL(e.request.url);
    const isAsset = url.pathname.startsWith('/libs/') || 
                    url.pathname.startsWith('/build/') || 
                    url.pathname.startsWith('/icons/') || 
                    url.pathname.endsWith('.css') || 
                    url.pathname.endsWith('.js') ||
                    url.pathname.endsWith('.woff2');

    if (isAsset) {
        // Cache-First strategy for assets to completely bypass Ngrok delay!
        e.respondWith(
            caches.match(e.request).then(function(cached) {
                if (cached) {
                    return cached; // Return instantly from phone memory
                }
                return fetch(e.request).then(function(response) {
                    if (response && response.status === 200) {
                        const cloned = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(e.request, cloned));
                    }
                    return response;
                });
            })
        );
    } else {
        // Network-First strategy for pages (HTML)
        e.respondWith(
            fetch(e.request)
                .then(function(response) {
                    if (response && response.status === 200 && response.type === 'basic') {
                        const cloned = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(e.request, cloned));
                    }
                    return response;
                })
                .catch(function() {
                    return caches.match(e.request).then(function(cached) {
                        return cached || caches.match(OFFLINE_URL);
                    });
                })
        );
    }
});
