document.addEventListener("DOMContentLoaded", async () => {
  if (typeof OfflineDB === "undefined" || typeof OfflineSession === "undefined") return;

  await OfflineSession.registerServiceWorker();

  const wrap = document.getElementById("offline-enter-wrap");
  const btn = document.getElementById("btn-offline-enter");
  const session = await OfflineSession.load();
  const valid = OfflineSession.isValid(session);

  if (valid && wrap) {
    wrap.classList.remove("d-none");
    if (session.nome) {
      const hint = wrap.querySelector(".offline-enter-hint");
      if (hint) hint.textContent = `Continuar como ${session.nome} (modo offline).`;
    }
  }

  if (!navigator.onLine && valid) {
    const entered = await OfflineSession.tryEnterOffline();
    if (entered) return;
  }

  btn?.addEventListener("click", async () => {
    const session = await OfflineSession.load();
    if (!OfflineSession.isValid(session)) {
      alert("Sessão offline expirada. Faça login com internet.");
      return;
    }
    await OfflineSession.tryEnterOffline();
  });
});
