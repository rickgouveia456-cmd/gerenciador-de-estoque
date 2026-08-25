const CACHE = 'logiprime-v1';
const OFFLINE = [
  '/',
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/assets/icons/logo.svg',
  '/assets/icons/icon-192.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(OFFLINE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(keys =>
    Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
  ).then(() => self.clients.claim()));
});

self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  const url = new URL(e.request.url);
  // Sempre buscar do servidor para rotas PHP dinâmicas
  if (url.origin === location.origin && !url.pathname.startsWith('/assets/')) {
    e.respondWith(
      fetch(e.request).catch(() =>
        caches.match('/') || new Response('<h1>Sem conexão</h1><p>Verifique sua internet.</p>', {headers:{'Content-Type':'text/html'}})
      )
    );
    return;
  }
  // Assets: cache-first
  e.respondWith(
    caches.match(e.request).then(cached => cached || fetch(e.request).then(res => {
      if (res.ok) { const c = res.clone(); caches.open(CACHE).then(cache => cache.put(e.request, c)); }
      return res;
    }))
  );
});