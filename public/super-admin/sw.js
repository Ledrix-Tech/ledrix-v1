/* Ledrix Super Admin PWA — network-first; does not change app behavior. */
const CACHE = 'ledrix-sa-pwa-v1';
const PRECACHE = [
  '/super-admin/icon-192.png',
  '/super-admin/icon-512.png',
  '/super-admin/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Always hit the network for pages and API — platform control stays live.
  if (event.request.mode === 'navigate' || !url.pathname.startsWith('/super-admin/')) {
    return;
  }

  // Cache-first only for PWA static assets under /super-admin/
  if (
    url.pathname.startsWith('/super-admin/icon-') ||
    url.pathname.endsWith('/manifest.webmanifest') ||
    url.pathname.endsWith('/sw.js')
  ) {
    event.respondWith(
      caches.match(event.request).then((cached) => cached || fetch(event.request))
    );
  }
});
