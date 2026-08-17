// Logi-Prime Service Worker — cache de assets estáticos
const CACHE_NAME = 'logiprime-v3';
const STATIC_ASSETS = [
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  // app.css é excluído intencionalmente — sempre busca do servidor para não cachear versões antigas
];

// Instala o SW e faz cache dos assets estáticos
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(STATIC_ASSETS).catch(() => {
        // Ignora erros de cache silenciosamente
      });
    })
  );
  self.skipWaiting();
});

// Limpa caches antigos ao ativar
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// Estratégia: Network First para páginas, Cache First para assets
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Ignora requisições não-GET e de outros domínios não essenciais
  if (event.request.method !== 'GET') return;

  // Assets estáticos (css, js, fonts, icons) — Cache First
  if (
    url.hostname === 'cdn.jsdelivr.net' ||
    url.pathname.startsWith('/static/')
  ) {
    event.respondWith(
      caches.match(event.request).then(cached => {
        if (cached) return cached;
        return fetch(event.request).then(response => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
          }
          return response;
        });
      })
    );
    return;
  }

  // Páginas da aplicação — Network First (dados sempre frescos)
  event.respondWith(
    fetch(event.request).catch(() => {
      // Se offline, tenta retornar do cache
      return caches.match(event.request);
    })
  );
});
