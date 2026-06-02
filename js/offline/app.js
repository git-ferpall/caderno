const OfflineApp = (() => {
  let enabled = null;
  let syncing = false;
  const nativeFetch = window.fetch.bind(window);

  function jsonResponse(obj, status = 200) {
    return new Response(JSON.stringify(obj), {
      status,
      headers: { "Content-Type": "application/json; charset=utf-8" },
    });
  }

  function isCatalogGet(method, url) {
    return method === "GET" && !!OfflineSync.getCacheKeyFromUrl(url);
  }

  async function serveCatalogFromCache(cacheKey) {
    const list = await OfflineSync.getCachedList(cacheKey);
    return jsonResponse(list ?? []);
  }

  async function patchFetch() {
    if (window.__offlineFetchPatched) return;
    window.__offlineFetchPatched = true;

    window.fetch = async function offlineFetch(input, init = {}) {
      const url = typeof input === "string" ? input : input.url;
      const method = (init.method || "GET").toUpperCase();
      const cacheKey = OfflineSync.getCacheKeyFromUrl(url);

      if (enabled === true && OfflineSync.isRelatorioUrl(url) && !navigator.onLine) {
        return jsonResponse({ ok: false, msg: "Relatórios exigem conexão com a internet." }, 503);
      }

      if (isCatalogGet(method, url)) {
        if (!navigator.onLine) {
          return serveCatalogFromCache(cacheKey);
        }

        try {
          const res = await nativeFetch(input, init);
          if (res.ok) {
            res
              .clone()
              .json()
              .then((data) => {
                if (Array.isArray(data)) OfflineSync.mergeDadosSlice(cacheKey, data);
              })
              .catch(() => {});
          }
          return res;
        } catch {
          const cached = await OfflineSync.getCachedList(cacheKey);
          if (cached) return jsonResponse(cached);
          throw new Error("Sem conexão e sem dados locais de " + cacheKey);
        }
      }

      if (
        enabled === true &&
        !navigator.onLine &&
        method === "POST" &&
        init.body instanceof FormData &&
        OfflineSync.isSalvarUrl(url)
      ) {
        await OfflineSync.enqueue(url, init.body);
        await updatePendingUI();
        OfflineUI.setBanner("Sem internet — apontamento salvo no dispositivo.", "info");
        OfflineUI.showOfflineSavedPopup();
        return jsonResponse({
          ok: true,
          offline: true,
          msg: "Salvo localmente. Sincroniza quando houver internet.",
        });
      }

      return nativeFetch(input, init);
    };
  }

  async function loadConfig() {
    try {
      const r = await nativeFetch("../funcoes/offline/config.php", { credentials: "same-origin" });
      const data = await r.json();
      if (data.ok && data.habilitado && typeof OfflineSession !== "undefined") {
        await OfflineSession.save(data);
      }
      enabled = !!data.habilitado;
      window.__offlineIsAdmin = !!data.is_admin;
      return data;
    } catch {
      if (typeof OfflineSession !== "undefined") {
        const session = await OfflineSession.load();
        if (OfflineSession.isValid(session)) {
          enabled = true;
          window.__offlineIsAdmin = !!session.is_admin;
          return {
            ok: true,
            habilitado: true,
            is_admin: !!session.is_admin,
            user_id: session.user_id,
            nome: session.nome,
            offline_session: true,
          };
        }
      }
      enabled = false;
      return { habilitado: false };
    }
  }

  async function refreshIfOnline() {
    if (enabled !== true || !navigator.onLine) return;
    try {
      await OfflineSync.refreshDados();
    } catch (e) {
      console.warn("[offline] cache dados:", e);
    }
  }

  async function warnIfCatalogEmpty() {
    if (!navigator.onLine && enabled === true) {
      const dados = await OfflineSync.getDadosCache();
      if (!OfflineSync.hasCatalogData(dados)) {
        OfflineUI.setBanner(
          "Áreas e produtos não estão neste aparelho. Abra o Caderno com internet uma vez para baixá-los.",
          "warn"
        );
      }
    }
  }

  async function updatePendingUI() {
    if (enabled !== true) return;
    const n = await OfflineDB.countFila();
    await OfflineUI.updateBadge(n);
    if (!navigator.onLine) {
      OfflineUI.setBanner("Modo offline — apontamentos serão salvos no dispositivo.", "info");
    }
  }

  async function runSync() {
    if (enabled !== true || syncing || !navigator.onLine) return;
    syncing = true;
    OfflineUI.setBanner("Sincronizando apontamentos...", "sync");
    try {
      const result = await OfflineSync.syncAll();
      await updatePendingUI();
      if (result.ok > 0) {
        OfflineUI.setBanner(`${result.ok} apontamento(s) sincronizado(s).`, "ok");
        setTimeout(() => OfflineUI.hideBanner(), 4000);
      } else if (result.fail > 0) {
        OfflineUI.setBanner("Alguns apontamentos não sincronizaram. Tente novamente.", "warn");
      } else {
        OfflineUI.hideBanner();
      }
    } catch {
      OfflineUI.setBanner("Erro ao sincronizar.", "warn");
    } finally {
      syncing = false;
    }
  }

  function bindEvents() {
    window.addEventListener("online", () => {
      if (enabled !== true) return;
      OfflineUI.hideBanner();
      refreshIfOnline().then(runSync);
    });

    window.addEventListener("offline", () => {
      if (enabled !== true) return;
      OfflineUI.setBanner("Modo offline — apontamentos serão salvos no dispositivo.", "info");
      warnIfCatalogEmpty();
    });
  }

  async function init() {
    if (typeof OfflineSync !== "undefined") {
      await OfflineSync.warmDadosCache();
    }

    if (typeof OfflineSession !== "undefined") {
      await OfflineSession.registerServiceWorker();
    }

    const config = await loadConfig();
    if (!config.habilitado) return;

    bindEvents();
    if (typeof OfflineNavigation !== "undefined") {
      OfflineNavigation.install(() => enabled === true);
    }
    OfflineUI.blockRelatoriosPage();
    if (navigator.onLine && typeof OfflineSession !== "undefined") {
      await OfflineSession.requestPrecache();
    }
    await refreshIfOnline();
    await updatePendingUI();
    await warnIfCatalogEmpty();
    if (navigator.onLine) await runSync();
  }

  patchFetch();

  return { init, isEnabled: () => enabled === true };
})();

document.addEventListener("DOMContentLoaded", () => {
  OfflineApp.init();
});

window.OfflineApp = OfflineApp;
