/**
 * Verifica se o servidor responde (além de navigator.onLine).
 */
const OfflineConnectivity = (() => {
  let lastOk = null;
  let lastAt = 0;
  const TTL_MS = 8000;

  async function pingServer(fetchFn) {
    const fn = fetchFn || window.__nativeFetch || fetch;
    try {
      const ctrl = new AbortController();
      const t = setTimeout(() => ctrl.abort(), 4000);
      const r = await fn("/funcoes/offline/ping.php", {
        credentials: "same-origin",
        cache: "no-store",
        signal: ctrl.signal,
      });
      clearTimeout(t);
      if (!r.ok) return false;
      const data = await r.json().catch(() => ({}));
      return !!(data.ok && data.pong);
    } catch {
      return false;
    }
  }

  async function hasServerReachable(force = false) {
    if (!navigator.onLine) {
      lastOk = false;
      lastAt = Date.now();
      return false;
    }
    if (!force && lastAt && Date.now() - lastAt < TTL_MS && lastOk !== null) {
      return lastOk;
    }
    lastOk = await pingServer();
    lastAt = Date.now();
    return lastOk;
  }

  function invalidate() {
    lastOk = null;
    lastAt = 0;
  }

  return { hasServerReachable, pingServer, invalidate };
})();

window.OfflineConnectivity = OfflineConnectivity;
