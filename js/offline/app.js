const OfflineApp = (() => {
  let enabled = null;
  let syncing = false;
  let preparing = false;
  const nativeFetch = window.fetch.bind(window);
  window.__nativeFetch = nativeFetch;

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

  async function isPreparedOnDevice() {
    try {
      const prep = await OfflineDB.getCache("offline_prepared");
      return !!(prep && prep.at);
    } catch {
      return false;
    }
  }

  async function canQueueOfflineSave() {
    if (enabled === true) return true;
    if (await isPreparedOnDevice()) return true;
    if (typeof OfflineSession === "undefined") return false;
    const session = await OfflineSession.load();
    return OfflineSession.isValid(session);
  }

  async function queueSalvarAndRespond(salvarUrl, formData) {
    try {
      await OfflineSync.enqueue(salvarUrl, formData);
      enabled = true;
      await updatePendingUI();
      OfflineUI.setBanner("Apontamento salvo neste aparelho. Sincroniza com internet.", "ok");
      OfflineUI.showOfflineSavedPopup();
      if (typeof OfflineBackgroundSync !== "undefined") {
        OfflineBackgroundSync.register();
      }
      return jsonResponse({
        ok: true,
        offline: true,
        msg: "Salvo localmente. Sincroniza quando houver internet.",
      });
    } catch (e) {
      console.error("[offline] fila:", e);
      return jsonResponse(
        {
          ok: false,
          err: "Não foi possível gravar no aparelho. Verifique se não está em aba anônima.",
        },
        500
      );
    }
  }

  function nativeFetchWithTimeout(input, init = {}, ms = 20000) {
    if (init.signal) return nativeFetch(input, init);
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), ms);
    return nativeFetch(input, { ...init, signal: controller.signal }).finally(() => clearTimeout(timer));
  }

  function blurFormSubmitters(form) {
    if (!form || !form.querySelectorAll) return;
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((el) => el.blur());
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
          const cached = await OfflineSync.getCachedList(cacheKey);
          return jsonResponse(cached && cached.length ? cached : []);
        }

        const catalogUrl = OfflineSync.getCatalogApiUrl(cacheKey) || url;
        try {
          const res = await nativeFetch(catalogUrl, init);
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

      const syncHeader =
        (typeof OfflineConstants !== "undefined" && OfflineConstants.SYNC_HEADER) || "X-Offline-Sync";
      const hdrs = new Headers(init.headers || {});
      if (hdrs.get(syncHeader) === "1") {
        return nativeFetch(input, init);
      }

      const salvarUrl = OfflineSync.resolveSalvarUrl(url);
      if (method === "POST" && init.body instanceof FormData && salvarUrl) {
        const canQueue = await canQueueOfflineSave();

        if (!navigator.onLine) {
          return queueSalvarAndRespond(salvarUrl, init.body);
        }

        const serverUp =
          typeof OfflineConnectivity !== "undefined"
            ? await OfflineConnectivity.hasServerReachable()
            : navigator.onLine;

        if (!serverUp && canQueue) {
          return queueSalvarAndRespond(salvarUrl, init.body);
        }

        if (canQueue && (enabled === true || (await isPreparedOnDevice()))) {
          try {
            return await nativeFetchWithTimeout(salvarUrl, init, 6000);
          } catch {
            return queueSalvarAndRespond(salvarUrl, init.body);
          }
        }

        try {
          return await nativeFetchWithTimeout(salvarUrl, init, 20000);
        } catch (err) {
          if (canQueue) {
            return queueSalvarAndRespond(salvarUrl, init.body);
          }
          return jsonResponse(
            { ok: false, err: "Falha ao enviar. Verifique a internet e tente novamente." },
            503
          );
        }
      }

      return nativeFetch(input, init);
    };
  }

  async function loadConfig() {
    try {
      const r = await nativeFetch("/funcoes/offline/config.php", { credentials: "same-origin" });
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
      if (await isPreparedOnDevice()) {
        enabled = true;
        return { ok: true, habilitado: true, offline_prepared: true };
      }
      enabled = false;
      return { habilitado: false };
    }
  }

  async function ensureHomeShellCached() {
    if (!navigator.onLine) return;
    try {
      await nativeFetch("/home", { credentials: "same-origin" });
    } catch {
      /* SW grava no cache ao responder */
    }
  }

  function scheduleCatalogRefill() {
    const run = () => OfflineSync.refillCatalogSelects().catch(() => {});
    run();
    [300, 800, 2000].forEach((ms) => setTimeout(run, ms));
  }

  async function refreshIfOnline() {
    if (enabled !== true || !navigator.onLine) return;
    try {
      await OfflineSync.refreshDados(nativeFetch);
      if (typeof OfflineCatalogMeta !== "undefined") {
        await OfflineCatalogMeta.warnIfNeeded();
      }
    } catch (e) {
      console.warn("[offline] cache dados:", e);
    }
  }

  async function warnIfCatalogEmpty() {
    if (!navigator.onLine && enabled === true) {
      const status = await OfflineSync.getCatalogStatus();
      if (!status.areas && !status.produtos) {
        OfflineUI.setBanner(
          "Áreas e produtos não estão neste aparelho. Use o menu «Baixar para offline» com internet.",
          "warn"
        );
      }
    }
  }

  async function updatePendingUI() {
    const n = await OfflineDB.countFila();
    if (typeof OfflinePendingPanel !== "undefined") {
      await OfflinePendingPanel.refresh();
    }
    if (enabled === true || (await canQueueOfflineSave())) {
      await OfflineUI.updateBadge(n);
    }
    if (!navigator.onLine && (enabled === true || (await canQueueOfflineSave()))) {
      OfflineUI.setBanner("Modo offline — apontamentos serão salvos no dispositivo.", "info");
    }
  }

  async function prepareForOffline() {
    if (preparing) return;
    if (!navigator.onLine) {
      OfflineUI.setBanner("Conecte-se à internet para baixar para offline.", "warn");
      return;
    }
    preparing = true;
    try {
      OfflineUI.setBanner("Preparando offline: verificando sessão…", "sync");

      const config = await loadConfig();
      if (!config.habilitado) {
        OfflineUI.setBanner("Modo offline não disponível.", "warn");
        return;
      }
      enabled = true;

      OfflineUI.setBanner("Preparando offline: baixando áreas, produtos e listas…", "sync");
      await OfflineSync.loadManifest(nativeFetch);
      await OfflineSync.refreshDados(nativeFetch);
      const catalogErrors = await OfflineSync.syncCatalogFromApis(nativeFetch);
      if (catalogErrors.length) {
        console.warn("[offline] avisos catálogo:", catalogErrors.join(", "));
      }

      OfflineUI.setBanner("Preparando offline: baixando telas (0%)…", "sync");
      const pages = await OfflineSession.precachePagesClient(
        ({ current, total, url }) => {
          const pct = Math.round((current / total) * 100);
          const short = url.replace(/^\//, "");
          OfflineUI.setBanner(`Preparando offline: telas ${pct}% (${short})…`, "sync");
        },
        nativeFetch
      );

      OfflineUI.setBanner("Preparando offline: finalizando…", "sync");
      await ensureHomeShellCached();

      const status = await OfflineSync.getCatalogStatus();
      const parts = [];
      if (status.areas) parts.push(`${status.areas} área(s)`);
      if (status.produtos) parts.push(`${status.produtos} produto(s)`);

      if (!status.areas && !status.produtos) {
        const propHint = status.propriedade
          ? ` Propriedade ativa: ${status.propriedade}.`
          : " Cadastre uma propriedade ativa no sistema.";
        throw new Error(
          "Nenhuma área ou produto foi baixado." + propHint + " Cadastre áreas e produtos online e tente de novo."
        );
      }

      scheduleCatalogRefill();

      const dados = await OfflineSync.getDadosCache();
      if (typeof OfflineCatalogMeta !== "undefined") {
        await OfflineCatalogMeta.savePreparedMeta(dados);
      } else {
        await OfflineDB.putCache("offline_prepared", { at: Date.now() });
      }

      const msg =
        `Pronto para offline: ${pages.ok}/${pages.total} telas` +
        (parts.length ? `, ${parts.join(", ")}` : "") +
        (pages.fail ? ` (${pages.fail} tela(s) com aviso)` : "") +
        ".";

      OfflineUI.setBanner(msg, "ok");
      if (typeof showPopup === "function") {
        showPopup("success", msg);
      }
      setTimeout(() => OfflineUI.hideBanner(), 6000);
    } catch (e) {
      console.warn("[offline] prepare:", e);
      OfflineUI.setBanner(
        "Não foi possível concluir o download. Tente de novo com internet estável.",
        "warn"
      );
    } finally {
      preparing = false;
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
    document.addEventListener(
      "submit",
      (e) => {
        if (e.target instanceof HTMLFormElement) {
          setTimeout(() => blurFormSubmitters(e.target), 0);
        }
      },
      true
    );

    window.addEventListener("online", () => {
      if (typeof OfflineConnectivity !== "undefined") OfflineConnectivity.invalidate();
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
      await OfflineSync.loadManifest(nativeFetch).catch(() => {});
      await OfflineSync.warmDadosCache();
    }

    if (typeof OfflineSession !== "undefined") {
      await OfflineSession.registerServiceWorker();
    }

    const config = await loadConfig();
    const canWork = config.habilitado || (await canQueueOfflineSave());
    const offlineNow = !navigator.onLine;

    if (!canWork && !offlineNow) return;

    if (!config.habilitado && (await canQueueOfflineSave())) {
      enabled = true;
    }

    bindEvents();

    if (!canWork && offlineNow) {
      OfflineUI.setBanner("Modo offline — apontamentos serão salvos no dispositivo.", "info");
      await updatePendingUI();
      scheduleCatalogRefill();
      return;
    }
    if (typeof OfflineBackgroundSync !== "undefined") {
      OfflineBackgroundSync.installListener(() => runSync());
    }
    if (typeof OfflineNavigation !== "undefined") {
      OfflineNavigation.install(() => enabled === true);
    }
    OfflineUI.blockRelatoriosPage();
    OfflineUI.warnIncognitoIfNeeded();
    OfflineUI.installPrepareButton(() => prepareForOffline(), () => enabled === true);
    if (navigator.onLine && typeof OfflineSession !== "undefined") {
      await OfflineSession.requestPrecache();
      await ensureHomeShellCached();
    }
    await refreshIfOnline();
    if (typeof OfflineCatalogMeta !== "undefined") {
      await OfflineCatalogMeta.warnIfNeeded();
    }
    await warnIfCatalogEmpty();
    if (!navigator.onLine || enabled === true) {
      scheduleCatalogRefill();
    }
    await updatePendingUI();
    if (navigator.onLine) await runSync();
  }

  patchFetch();

  return {
    init,
    isEnabled: () => enabled === true,
    prepareForOffline,
    isPreparing: () => preparing,
    runSync,
  };
})();

document.addEventListener("DOMContentLoaded", () => {
  OfflineApp.init();
});

window.OfflineApp = OfflineApp;
