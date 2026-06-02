/**
 * Background Sync API — dispara sincronização quando a conexão voltar (Chrome/Edge/Android).
 */
const OfflineBackgroundSync = (() => {
  const TAG = "caderno-fila-sync";

  function supported() {
    return "serviceWorker" in navigator && "SyncManager" in window;
  }

  async function register() {
    if (!supported()) return false;
    try {
      const reg = await navigator.serviceWorker.ready;
      await reg.sync.register(TAG);
      return true;
    } catch (e) {
      console.warn("[offline] background sync:", e);
      return false;
    }
  }

  function installListener(onSync) {
    if (!("serviceWorker" in navigator)) return;
    navigator.serviceWorker.addEventListener("message", (event) => {
      const data = event.data || {};
      if (data.type === "RUN_OFFLINE_SYNC" && typeof onSync === "function") {
        onSync();
      }
    });
  }

  return { TAG, supported, register, installListener };
})();

window.OfflineBackgroundSync = OfflineBackgroundSync;
