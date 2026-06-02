const OfflineSession = (() => {
  const SESSION_KEY = "offline_session";
  const DEFAULT_DAYS = 30;

  const SHELL_PAGES = [
    "/home",
    "/home/apontamento",
    "/home/Plantio",
    "/home/Transplantio",
    "/home/Colheita",
    "/home/Climatico",
    "/home/Fertilizante",
    "/home/Herbicida",
    "/home/Fungicida",
    "/home/Inseticida",
    "/home/AdubacaoCalcario",
    "/home/AdubacaoOrganica",
    "/home/Irrigacao",
    "/home/ControleAgua",
    "/home/MoscaFrutas",
    "/home/PragasDoencas",
    "/home/ManejoIntegrado",
    "/home/Erradicacao",
    "/home/RevisaoMaquinas",
    "/home/ColetaAnalise",
    "/home/VisitaTecnica",
    "/home/Personalizado",
    "/home/hidroponia",
  ];

  function isValid(session) {
    if (!session || !session.habilitado) return false;
    if (!session.expiresAt) return false;
    return Date.now() < session.expiresAt;
  }

  async function load() {
    return OfflineDB.getCache(SESSION_KEY);
  }

  async function save(config) {
    if (!config?.habilitado) return null;

    const prev = await load();
    if (prev?.user_id && config.user_id && prev.user_id !== config.user_id && typeof OfflineDB !== "undefined") {
      if (OfflineDB.clearFilaAll) await OfflineDB.clearFilaAll();
      await OfflineDB.putCache("offline_prepared", null);
    }

    const days = config.session_days || DEFAULT_DAYS;
    const session = {
      user_id: config.user_id,
      nome: config.nome || "Usuário",
      habilitado: true,
      is_admin: !!config.is_admin,
      savedAt: Date.now(),
      expiresAt: Date.now() + days * 24 * 60 * 60 * 1000,
    };
    await OfflineDB.putCache(SESSION_KEY, session);
    if (navigator.onLine && typeof OfflineSync !== "undefined") {
      try {
        await OfflineSync.refreshDados(window.__nativeFetch || fetch);
      } catch (e) {
        console.warn("[offline] refresh dados no login:", e);
      }
    }
    await requestPrecache();
    return session;
  }

  async function clear() {
    await OfflineDB.putCache(SESSION_KEY, null);
    await OfflineDB.putCache("offline_prepared", null);
    await OfflineDB.putCache("offline_manifest", null);
    if (typeof OfflineConnectivity !== "undefined") OfflineConnectivity.invalidate();
    if ("serviceWorker" in navigator) {
      const reg = await navigator.serviceWorker.ready.catch(() => null);
      reg?.active?.postMessage({ type: "CLEAR_PAGE_CACHE" });
    }
  }

  /**
   * Antes de sair — avisa se há fila local (dados permanecem no aparelho).
   */
  async function clearBeforeLogout(event) {
    if (typeof OfflineDB === "undefined") {
      await clear();
      return true;
    }
    let n = 0;
    try {
      n = await OfflineDB.countFila();
    } catch {
      n = 0;
    }
    if (n > 0) {
      const msg =
        n === 1
          ? "Há 1 apontamento não sincronizado neste aparelho. Se sair, ele continua na fila local até você sincronizar com o mesmo usuário."
          : `Há ${n} apontamentos não sincronizados neste aparelho. Se sair, eles continuam na fila até sincronizar.`;
      const ok = confirm(msg + "\n\nDeseja sair mesmo assim?");
      if (!ok) {
        event?.preventDefault();
        return false;
      }
    }
    await clear();
    return true;
  }

  async function requestPrecache() {
    if (!("serviceWorker" in navigator)) return;
    const reg = await navigator.serviceWorker.register("/sw.js").catch(() => null);
    if (!reg) return;
    await navigator.serviceWorker.ready;
    reg.active?.postMessage({ type: "PRECACHE_PAGES", urls: SHELL_PAGES });
  }

  /**
   * Baixa cada tela no dispositivo (via fetch; o Service Worker grava no cache).
   * @param {(info: { current: number, total: number, url: string }) => void} [onProgress]
   * @param {typeof fetch} [fetchFn]
   */
  async function precachePagesClient(onProgress, fetchFn = fetch) {
    let ok = 0;
    let fail = 0;
    const total = SHELL_PAGES.length;

    for (let i = 0; i < SHELL_PAGES.length; i++) {
      const url = SHELL_PAGES[i];
      onProgress?.({ current: i + 1, total, url });
      try {
        const res = await fetchFn(url, { credentials: "same-origin" });
        const path = new URL(res.url, location.origin).pathname;
        const isLogin = path === "/" || path === "/index.php";
        if (res.ok && !isLogin) ok++;
        else fail++;
      } catch {
        fail++;
      }
    }

    if ("serviceWorker" in navigator) {
      const reg = await navigator.serviceWorker.ready.catch(() => null);
      reg?.active?.postMessage({ type: "PRECACHE_PAGES", urls: SHELL_PAGES });
    }

    return { ok, fail, total };
  }

  async function tryEnterOffline() {
    const session = await load();
    if (!isValid(session)) return false;
    window.location.href = "/home";
    return true;
  }

  async function registerServiceWorker() {
    if (!("serviceWorker" in navigator)) return null;
    try {
      return await navigator.serviceWorker.register("/sw.js");
    } catch {
      return null;
    }
  }

  return {
    SHELL_PAGES,
    isValid,
    load,
    save,
    clear,
    clearBeforeLogout,
    requestPrecache,
    precachePagesClient,
    tryEnterOffline,
    registerServiceWorker,
  };
})();

window.OfflineSession = OfflineSession;
