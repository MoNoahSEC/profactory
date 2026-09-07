const CACHE_NAME = 'profactory-v7';
const OFFLINE_URL = '/offline.html';

// Static assets for offline speed & instant PWA boot
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
    '/libs/js/turbo.js'
];

// Install: Cache essential assets
self.addEventListener('install', function(e) {
    e.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.addAll(STATIC_CACHE).catch(function(err) {
                console.log('Cache error on install:', err);
            });
        })
    );
    self.skipWaiting();
});

// Activate: Clear legacy caches
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

// Fetch Strategy: Assets Cache-First, HTML Network-First with Offline Fallback
self.addEventListener('fetch', function(e) {
    if (e.request.method !== 'GET') return;
    
    const url = new URL(e.request.url);
    const isAsset = url.pathname.startsWith('/libs/') || 
                    url.pathname.startsWith('/build/') || 
                    url.pathname.startsWith('/icons/') || 
                    url.pathname.startsWith('/images/') || 
                    url.pathname.endsWith('.css') || 
                    url.pathname.endsWith('.js') ||
                    url.pathname.endsWith('.woff2') ||
                    url.pathname.endsWith('.png') ||
                    url.pathname.endsWith('.jpg');

    if (isAsset) {
        e.respondWith(
            caches.match(e.request).then(function(cached) {
                if (cached) return cached;
                return fetch(e.request).then(function(response) {
                    if (response && response.status === 200) {
                        const cloned = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(e.request, cloned));
                    }
                    return response;
                }).catch(() => null);
            })
        );
    } else {
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

// ══════════════════════════════════════════════════════════
// 🔔 PUSH NOTIFICATION EVENT HANDLER
// ══════════════════════════════════════════════════════════
self.addEventListener('push', function(event) {
    let data = {
        title: 'مصنع سالم علي',
        body: 'لديك إشعار وتنبيه جديد في النظام!',
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-96.png',
        url: '/dashboard',
        tag: 'profactory-alert'
    };

    if (event.data) {
        try {
            const parsed = event.data.json();
            data = Object.assign(data, parsed);
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/icons/icon-192.png',
        badge: data.badge || '/icons/icon-96.png',
        image: data.image || null,
        tag: data.tag || 'profactory-general',
        renotify: true,
        vibrate: [200, 100, 200, 100, 200],
        dir: 'rtl',
        lang: 'ar',
        data: {
            url: data.url || '/dashboard',
            time: new Date().toISOString()
        },
        actions: [
            {
                action: 'open_url',
                title: 'عرض الآن'
            },
            {
                action: 'dismiss',
                title: 'إغلاق'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// ══════════════════════════════════════════════════════════
// 🎯 NOTIFICATION CLICK & DEEP LINKING
// ══════════════════════════════════════════════════════════
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    if (event.action === 'dismiss') {
        return;
    }

    const targetUrl = (event.notification.data && event.notification.data.url) 
        ? event.notification.data.url 
        : '/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            // If already open, focus it and navigate
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url && 'focus' in client) {
                    client.focus();
                    if ('navigate' in client) {
                        return client.navigate(targetUrl);
                    }
                    return;
                }
            }
            // Otherwise open a new window
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// ══════════════════════════════════════════════════════════
// 📨 CLIENT MESSAGE EVENT (For in-app trigger)
// ══════════════════════════════════════════════════════════
self.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'SHOW_NOTIFICATION') {
        const title = event.data.title || 'مصنع سالم علي';
        const options = {
            body: event.data.body || '',
            icon: '/icons/icon-192.png',
            badge: '/icons/icon-96.png',
            vibrate: [200, 100, 200],
            dir: 'rtl',
            lang: 'ar',
            data: {
                url: event.data.url || '/dashboard'
            }
        };
        self.registration.showNotification(title, options);
    }
});
