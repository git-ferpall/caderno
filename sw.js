const CACHE_STATIC = "caderno-static-v2";
const CACHE_PAGES = "caderno-pages-v2";

const STATIC_ASSETS = [
  "/css/style.css",
  "/js/jquery.js",
  "/js/main.js",
  "/js/popups.js",
  "/js/script.js",
  "/js/offline/db.js",
  "/js/offline/sync.js",
  "/js/offline/ui.js",
  "/js/offline/session.js",
  "/js/offline/app.js",
  "/js/offline/navigation.js",
  "/img/logo-icon.png",
  "/img/logo-color.png",
  "/manifest.webmanifest",
  "/offline.html",
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_STATIC).then((cache) => cache.addAll(STATIC_ASSETS).catch(() => {})).then(() => self.skipWaiting())
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_STATIC && k !== CACHE_PAGES).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener("message", (event) => {
  const data = event.data || {};
  if (data.type === "PRECACHE_PAGES" && Array.isArray(data.urls)) {
    event.waitUntil(precachePages(data.urls));
  }
  if (data.type === "CLEAR_PAGE_CACHE") {
    event.waitUntil(caches.delete(CACHE_PAGES));
  }
});

function isLoginPath(pathname) {
  return pathname === "/" || pathname === "/index.php";
}

function pageCacheKeys(pathname, responseUrl) {
  const keys = new Set();
  const base = pathname.replace(/\/$/, "") || "/";
  const paths = [base, `${base}/`, `${base}.php`];
  if (responseUrl) {
    const respPath = new URL(responseUrl).pathname.replace(/\/$/, "") || "/";
    paths.push(respPath, `${respPath}/`, `${respPath}.php`);
  }
  if (base.startsWith("/home/") && base !== "/home") {
    const segment = base.split("/").filter(Boolean).pop();
    paths.push(`/home/${segment}.php`);
  }
  paths.forEach((p) => {
    keys.add(p);
    keys.add(self.location.origin + p);
  });
  return [...keys];
}

async function putPageInCache(cache, request, response) {
  const url = new URL(request.url);
  const keys = pageCacheKeys(url.pathname, response.url);
  await Promise.all(
    keys.map((key) => cache.put(key, response.clone()))
  );
}

async function findCachedPage(request) {
  const cache = await caches.open(CACHE_PAGES);
  const url = new URL(request.url);
  const keys = pageCacheKeys(url.pathname, null);
  for (const key of keys) {
    const hit = await cache.match(key);
    if (hit) return hit;
  }
  return cache.match(request);
}

async function precachePages(urls) {
  const cache = await caches.open(CACHE_PAGES);
  await Promise.all(
    urls.map((url) =>
      fetch(url, { credentials: "include" })
        .then(async (res) => {
          if (!res.ok || isLoginPath(new URL(res.url).pathname)) return;
          const req = new Request(url, { credentials: "include" });
          await putPageInCache(cache, req, res);
        })
        .catch(() => {})
    )
  );
}

function isStaticAsset(pathname) {
  return (
    pathname.startsWith("/css/") ||
    pathname.startsWith("/js/") ||
    pathname.startsWith("/img/") ||
    pathname.endsWith(".webmanifest")
  );
}

function isAppShell(pathname) {
  if (pathname === "/" || pathname === "/index.php") return true;
  if (pathname.startsWith("/home")) return true;
  return false;
}

self.addEventListener("fetch", (event) => {
  const url = new URL(event.request.url);
  if (url.origin !== self.location.origin) return;
  if (url.pathname.includes("/funcoes/relatorios/")) return;
  if (event.request.method !== "GET") return;

  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirstStatic(event.request));
    return;
  }

  if (event.request.mode === "navigate" || (event.request.headers.get("accept") || "").includes("text/html")) {
    if (isAppShell(url.pathname)) {
      event.respondWith(networkFirstPage(event.request));
      return;
    }
  }
});

async function cacheFirstStatic(request) {
  const cached = await caches.match(request);
  if (cached) return cached;
  const res = await fetch(request);
  const copy = res.clone();
  caches.open(CACHE_STATIC).then((cache) => cache.put(request, copy));
  return res;
}

function offlineSubpageHtml(pathname) {
  const title = "Tela não disponível offline";
  const body =
    pathname.startsWith("/home/") && pathname !== "/home"
      ? "<p>Esta página ainda não foi salva neste aparelho.</p><p>Com internet, abra <strong>Início</strong> e toque em <strong>Novo apontamento</strong> uma vez; depois o offline funciona aqui.</p>"
      : "<p>Sem conexão. Faça login com internet pelo menos uma vez neste aparelho.</p>";
  return `<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${title}</title><link rel="stylesheet" href="/css/style.css"></head><body class="offline-entry-page"><main class="offline-entry-main"><h1>${title}</h1>${body}<p><a href="/home" class="main-btn fundo-azul">Voltar ao início</a></p></main></body></html>`;
}

async function networkFirstPage(request) {
  const url = new URL(request.url);
  try {
    const res = await fetch(request);
    if (res.ok && !isLoginPath(new URL(res.url).pathname)) {
      const cache = await caches.open(CACHE_PAGES);
      putPageInCache(cache, request, res.clone());
    }
    return res;
  } catch {
    const cached = await findCachedPage(request);
    if (cached) return cached;
    if (url.pathname.startsWith("/home/") && url.pathname !== "/home") {
      return new Response(offlineSubpageHtml(url.pathname), {
        status: 503,
        headers: { "Content-Type": "text/html; charset=utf-8" },
      });
    }
    const offline = await caches.match("/offline.html");
    if (offline) return offline;
    return new Response("Sem conexão. Abra o Caderno online pelo menos uma vez neste aparelho.", {
      status: 503,
      headers: { "Content-Type": "text/plain; charset=utf-8" },
    });
  }
}
