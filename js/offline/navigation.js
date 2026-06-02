const OfflineNavigation = (() => {
  const CACHE_PAGES =
    (typeof OfflineConstants !== "undefined" && OfflineConstants.CACHE_PAGES) ||
    "caderno-pages-v7";

  function normalizeAppPath(pathname) {
    const base = (pathname || "/").replace(/\/$/, "") || "/";
    if (base === "/home/home" || base === "/home/index") return "/home";
    return base;
  }

  function isInternalHomeLink(url) {
    return url.origin === window.location.origin && url.pathname.startsWith("/home");
  }

  function cacheKeyCandidates(href) {
    const url = new URL(href, window.location.href);
    const base = normalizeAppPath(url.pathname);
    const keys = new Set([url.href, base, `${base}/`, `${base}.php`]);
    if (base === "/home") {
      keys.add("/home/home");
      keys.add("/home/home/");
      keys.add("/home/home.php");
    }
    if (base.startsWith("/home/") && base !== "/home") {
      const segment = base.split("/").filter(Boolean).pop();
      keys.add(`/home/${segment}.php`);
      keys.add(`${url.origin}/home/${segment}.php`);
    }
    return [...keys];
  }

  function resolveNavUrl(rawHref) {
    const url = new URL(rawHref, window.location.href);
    const norm = normalizeAppPath(url.pathname);
    if (norm !== url.pathname.replace(/\/$/, "") && url.pathname.replace(/\/$/, "") !== norm) {
      return new URL(norm + url.search + url.hash, url.origin);
    }
    return url;
  }

  async function hasCachedPage(href) {
    if (!("caches" in window)) return true;
    const cache = await caches.open(CACHE_PAGES);
    for (const key of cacheKeyCandidates(href)) {
      if (await cache.match(key)) return true;
    }
    return false;
  }

  function install(isEnabled) {
    document.addEventListener(
      "click",
      async (e) => {
        if (!isEnabled() || navigator.onLine) return;
        const link = e.target.closest("a[href]");
        if (!link || link.target === "_blank" || link.hasAttribute("download")) return;

        const rawHref = link.getAttribute("href");
        if (!rawHref || rawHref.startsWith("#") || rawHref.startsWith("javascript:")) return;

        let url;
        try {
          url = resolveNavUrl(rawHref);
        } catch {
          return;
        }
        if (!isInternalHomeLink(url)) return;

        const cached = await hasCachedPage(url.href);
        if (cached) {
          if (url.href !== new URL(rawHref, window.location.href).href) {
            e.preventDefault();
            window.location.href = url.pathname + url.search + url.hash;
          }
          return;
        }

        e.preventDefault();
        e.stopPropagation();
        const msg =
          normalizeAppPath(url.pathname) === "/home"
            ? "A tela inicial ainda não foi baixada. Abra o Caderno com internet na home uma vez (use aba normal, não anônima)."
            : "Abra esta tela com internet uma vez neste aparelho para usá-la offline.";
        if (typeof OfflineUI !== "undefined") {
          OfflineUI.setBanner(msg, "warn");
        } else {
          alert(msg);
        }
      },
      true
    );
  }

  return { install, hasCachedPage, cacheKeyCandidates, normalizeAppPath, resolveNavUrl };
})();

window.OfflineNavigation = OfflineNavigation;
