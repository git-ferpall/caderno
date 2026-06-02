const OfflineApp = (() => {
  let enabled = false;
  let syncing = false;
  const nativeFetch = window.fetch.bind(window);

  function jsonResponse(obj, status = 200) {
    return new Response(JSON.stringify(obj), {
      status,
      headers: { "Content-Type": "application/json; charset=utf-8" },
    });
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
    if (!enabled || !navigator.onLine) return;
    try {
      await OfflineSync.refreshDados();
    } catch (e) {
      console.warn("[offline] cache dados:", e);
    }
  }

  async function updatePendingUI() {
    if (!enabled) return;
    const n = await OfflineDB.countFila();
    await OfflineUI.updateBadge(n);
    if (!navigator.onLine) {
      OfflineUI.setBanner("Modo offline — apontamentos serão salvos no dispositivo.", "info");
    }
  }

  async function runSync() {
    if (!enabled || syncing || !navigator.onLine) return;
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

  function patchFetch() {
    window.fetch = async function offlineFetch(input, init = {}) {
      const url = typeof input === "string" ? input : input.url;
      const method = (init.method || "GET").toUpperCase();

      if (enabled && OfflineSync.isRelatorioUrl(url) && !navigator.onLine) {
        return jsonResponse({ ok: false, msg: "Relatórios exigem conexão com a internet." }, 503);
      }

      if (enabled && method === "GET") {
        const cacheKey = OfflineSync.getCacheKeyFromUrl(url);
        if (cacheKey && !navigator.onLine) {
          const dados = await OfflineSync.getDadosCache();
          if (dados && Array.isArray(dados[cacheKey])) {
            return jsonResponse(dados[cacheKey]);
          }
          return jsonResponse([], 200);
        }
      }

      if (
        enabled &&
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

  function bindEvents() {
    window.addEventListener("online", () => {
      if (!enabled) return;
      OfflineUI.hideBanner();
      refreshIfOnline().then(runSync);
    });

    window.addEventListener("offline", () => {
      if (!enabled) return;
      OfflineUI.setBanner("Modo offline — apontamentos serão salvos no dispositivo.", "info");
    });
  }

  async function init() {
    if (typeof OfflineSession !== "undefined") {
      await OfflineSession.registerServiceWorker();
    }

    const config = await loadConfig();
    if (!config.habilitado) return;

    patchFetch();
    bindEvents();
    OfflineUI.blockRelatoriosPage();
    await refreshIfOnline();
    await updatePendingUI();
    if (navigator.onLine) await runSync();
  }

  return { init, isEnabled: () => enabled };
})();

document.addEventListener("DOMContentLoaded", () => {
  OfflineApp.init();
});

window.OfflineApp = OfflineApp;
