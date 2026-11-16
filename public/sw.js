/* E-Sabong report shell: cache the page frame so the app opens instantly. */
const CACHE = 'esabong-report-v1';
const SHELL = ['/css/mobile.css', '/icons/app.svg', '/js/live-refresh.js', '/js/mobile-report.js', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
    return;
  }

  // Reports must be fresh: network first, cache only as an offline fallback.
  event.respondWith(
    fetch(request)
      .then((response) => {
        if (response.ok && SHELL.indexOf(new URL(request.url).pathname) !== -1) {
          const copy = response.clone();
          caches.open(CACHE).then((cache) => cache.put(request, copy));
        }

        return response;
      })
      .catch(() => caches.match(request))
  );
});
