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
    await requestPrecache();
    return session;
  }

  async function clear() {
    await OfflineDB.putCache(SESSION_KEY, null);
    if ("serviceWorker" in navigator) {
      const reg = await navigator.serviceWorker.ready.catch(() => null);
      reg?.active?.postMessage({ type: "CLEAR_PAGE_CACHE" });
    }
  }

  async function requestPrecache() {
    if (!("serviceWorker" in navigator)) return;
    const reg = await navigator.serviceWorker.register("/sw.js").catch(() => null);
    if (!reg) return;
    await navigator.serviceWorker.ready;
    reg.active?.postMessage({ type: "PRECACHE_PAGES", urls: SHELL_PAGES });
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
    requestPrecache,
    tryEnterOffline,
    registerServiceWorker,
  };
})();

window.OfflineSession = OfflineSession;
