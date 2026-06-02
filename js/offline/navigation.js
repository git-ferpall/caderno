const OfflineNavigation = (() => {
  const CACHE_PAGES = "caderno-pages-v2";

  function isInternalHomeLink(url) {
    return url.origin === window.location.origin && url.pathname.startsWith("/home");
  }

  function cacheKeyCandidates(href) {
    const url = new URL(href, window.location.href);
    const base = url.pathname.replace(/\/$/, "") || "/";
    const keys = new Set([url.href, base, `${base}/`, `${base}.php`]);
    if (base.startsWith("/home/") && base !== "/home") {
      const segment = base.split("/").filter(Boolean).pop();
      keys.add(`/home/${segment}.php`);
      keys.add(`${url.origin}/home/${segment}.php`);
    }
    return [...keys];
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
          url = new URL(rawHref, window.location.href);
        } catch {
          return;
        }
        if (!isInternalHomeLink(url)) return;

        const cached = await hasCachedPage(url.href);
        if (cached) return;

        e.preventDefault();
        e.stopPropagation();
        const msg =
          "Abra esta tela com internet uma vez neste aparelho para usá-la offline.";
        if (typeof OfflineUI !== "undefined") {
          OfflineUI.setBanner(msg, "warn");
        } else {
          alert(msg);
        }
      },
      true
    );
  }

  return { install, hasCachedPage, cacheKeyCandidates };
})();

window.OfflineNavigation = OfflineNavigation;
