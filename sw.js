const CACHE_STATIC = "caderno-static-v15";
const CACHE_PAGES = "caderno-pages-v15";
const BG_SYNC_TAG = "caderno-fila-sync";

const STATIC_ASSETS = [
  "/css/style.css",
  "/js/jquery.js",
  "/js/main.js",
  "/js/popups.js",
  "/js/script.js",
  "/js/offline/constants.js",
  "/js/offline/connectivity.js",
  "/js/offline/background-sync.js",
  "/js/offline/catalog-meta.js",
  "/js/offline/db.js",
  "/js/offline/sync.js",
  "/js/offline/ui.js",
  "/js/offline/session.js",
  "/js/offline/app.js",
  "/js/offline/navigation.js",
  "/js/offline/catalog-ui.js",
  "/js/offline/pending-panel.js",
  "/js/offline/defensivo-outro.js",
  "/js/offline/salvar-form.js",
  "/js/semeadura.js",
  "/js/plantio.js",
  "/js/vendor/qrcodejs.min.js",
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
  if (data.type === "SKIP_WAITING") {
    self.skipWaiting();
    return;
  }
  if (data.type === "PRECACHE_PAGES" && Array.isArray(data.urls)) {
    event.waitUntil(precachePages(data.urls));
  }
  if (data.type === "CLEAR_PAGE_CACHE") {
    event.waitUntil(caches.delete(CACHE_PAGES));
  }
});

/** Background Sync — pede às abas abertas que sincronizem a fila */
self.addEventListener("sync", (event) => {
  if (event.tag === BG_SYNC_TAG) {
    event.waitUntil(notifyClientsRunSync());
  }
});

async function notifyClientsRunSync() {
  const clients = await self.clients.matchAll({ type: "window", includeUncontrolled: true });
  await Promise.all(
    clients.map((client) => client.postMessage({ type: "RUN_OFFLINE_SYNC" }))
  );
}

/** Só a raiz sem arquivo — index.php pode ser cacheado para entrar offline */
function isLoginPath(pathname) {
  return pathname === "/";
}

function isOfflineEntryPath(pathname) {
  return pathname === "/offline.html" || pathname === "/index.php";
}

/** /home/home (menu antigo) e /home/index → mesma tela que /home */
function normalizeAppPath(pathname) {
  const base = (pathname || "/").replace(/\/$/, "") || "/";
  if (base === "/home/home" || base === "/home/index") return "/home";
  return base;
}

function pageCacheKeys(pathname, responseUrl) {
  const keys = new Set();
  const base = normalizeAppPath(pathname);
  const paths = [base, `${base}/`, `${base}.php`];
  if (base === "/home") {
    paths.push("/home/home", "/home/home/", "/home/home.php", "/home/index.php");
  }
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
  const keys = pageCacheKeys(normalizeAppPath(url.pathname), response.url);
  await Promise.all(
    keys.map((key) => cache.put(key, response.clone()))
  );
}

async function findCachedPage(request) {
  const cache = await caches.open(CACHE_PAGES);
  const url = new URL(request.url);
  const keys = pageCacheKeys(normalizeAppPath(url.pathname), null);
  for (const key of keys) {
    const hit = await cache.match(key);
    if (hit) return hit;
  }
  return cache.match(request);
}

async function precachePages(urls) {
  const cache = await caches.open(CACHE_PAGES);
  await Promise.all(urls.map((url) => precacheOnePage(cache, url)));
}

/** Fetch que não segue redirect http:// (Mixed Content atrás de proxy nginx). */
async function fetchSecure(input, init = {}) {
  const baseInit = {
    credentials: init.credentials ?? "include",
    redirect: "manual",
    ...init,
    headers: { "X-Offline-Sync": "1", ...(init.headers || {}) },
  };

  let res = await fetch(input, baseInit);
  let hops = 0;

  while (hops < 5 && (res.type === "opaqueredirect" || (res.status >= 300 && res.status < 400))) {
    hops++;
    let loc = res.headers.get("Location");
    if (!loc && res.type === "opaqueredirect") break;
    if (!loc) break;
    if (loc.startsWith("http://")) loc = "https://" + loc.slice(7);
    else if (loc.startsWith("/")) loc = self.location.origin + loc;
    res = await fetch(loc, baseInit);
  }

  return res;
}

async function precacheOnePage(cache, url) {
  try {
    const res = await fetchSecure(url);
    if (res.type === "opaqueredirect" || (res.status >= 300 && res.status < 400)) return;
    const p = new URL(res.url).pathname;
    if (!res.ok || (isLoginPath(p) && !isOfflineEntryPath(p))) return;
    const req = new Request(url, { credentials: "include" });
    await putPageInCache(cache, req, res);
  } catch {
    /* ignore */
  }
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
  const norm = normalizeAppPath(pathname);
  const title = "Tela não disponível offline";
  let body;
  if (norm === "/home") {
    body =
      "<p>A tela inicial ainda não foi salva neste aparelho.</p><p>Com internet, abra o <strong>Caderno</strong> e aguarde a home carregar; depois o offline funciona.</p><p><strong>Não use aba anônima</strong> — o modo offline precisa de uma aba normal do navegador.</p>";
  } else if (norm.startsWith("/home/")) {
    body =
      "<p>Esta página ainda não foi salva neste aparelho.</p><p>Com internet, abra <strong>Início</strong> e o formulário desejado uma vez; depois o offline funciona aqui.</p>";
  } else {
    body = "<p>Sem conexão. Faça login com internet pelo menos uma vez neste aparelho.</p>";
  }
  return `<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${title}</title><link rel="stylesheet" href="/css/style.css"></head><body class="offline-entry-page"><main class="offline-entry-main"><h1>${title}</h1>${body}<p><a href="/offline.html" class="main-btn fundo-azul">Continuar offline</a></p></main></body></html>`;
}

async function networkFirstPage(request) {
  const url = new URL(request.url);
  try {
    // Navegação do usuário: seguir redirects do PHP (login → /home/).
    // fetchSecure (manual) quebra redirects e causa ERR_FAILED após login.
    const res = await fetch(request);
    const resPath = new URL(res.url).pathname;
    if (res.ok && (!isLoginPath(resPath) || isOfflineEntryPath(resPath))) {
      const cache = await caches.open(CACHE_PAGES);
      putPageInCache(cache, request, res.clone());
    }
    return res;
  } catch {
    const cached = await findCachedPage(request);
    if (cached) return cached;

    const offline = await caches.match("/offline.html");
    if (offline && (isLoginPath(url.pathname) || isOfflineEntryPath(url.pathname) || url.pathname === "/index.php")) {
      return offline;
    }

    const norm = normalizeAppPath(url.pathname);
    if (norm === "/home" || (norm.startsWith("/home/") && norm !== "/home")) {
      return new Response(offlineSubpageHtml(url.pathname), {
        status: 503,
        headers: { "Content-Type": "text/html; charset=utf-8" },
      });
    }
    if (offline) return offline;
    return new Response("Sem conexão. Use o mesmo navegador onde fez login e «Baixar para offline», ou abra /offline.html", {
      status: 503,
      headers: { "Content-Type": "text/plain; charset=utf-8" },
    });
  }
}
