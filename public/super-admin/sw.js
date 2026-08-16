/* Ledrix Super Admin PWA — network-first; does not change app behavior. */
const CACHE = 'ledrix-sa-pwa-v3';
const PRECACHE = [
  '/super-admin/icon-192.png?v=3',
  '/super-admin/icon-512.png?v=3',
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

  // Network-first for icons/manifest so favicon updates are not stuck on the old L tile.
  if (
    url.pathname.startsWith('/super-admin/icon-') ||
    url.pathname.endsWith('/manifest.webmanifest')
  ) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          const copy = response.clone();
          caches.open(CACHE).then((cache) => cache.put(event.request, copy));
          return response;
        })
        .catch(() => caches.match(event.request))
    );
  }
});
