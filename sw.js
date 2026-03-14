const CACHE_NAME = 'justsale-mobile-v1';
const ASSETS = [
  'mobile-pos.html',
  'assets/css/mobile-pos.css',
  'assets/js/mobile-pos.js',
  'assets/pwa/icon-512.png',
  'assets/vendor/fontawesome/css/all.min.css'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS);
    })
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});
