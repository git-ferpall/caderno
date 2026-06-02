const OfflineUI = (() => {
  let banner = null;
  let badge = null;

  function ensureBanner() {
    if (banner) return banner;
    banner = document.createElement("div");
    banner.id = "offline-banner";
    banner.className = "offline-banner d-none";
    banner.setAttribute("role", "status");
    document.body.appendChild(banner);
    return banner;
  }

  function ensureBadge() {
    if (badge) return badge;
    badge = document.createElement("div");
    badge.id = "offline-pending-badge";
    badge.className = "offline-pending-badge d-none";
    document.body.appendChild(badge);
    return badge;
  }

  function setBanner(text, type = "info") {
    const el = ensureBanner();
    el.textContent = text;
    el.className = `offline-banner offline-banner--${type}`;
    el.classList.remove("d-none");
  }

  function hideBanner() {
    banner?.classList.add("d-none");
  }

  async function updateBadge(count) {
    const el = ensureBadge();
    if (count > 0) {
      el.textContent = count === 1 ? "1 apontamento aguardando sync" : `${count} apontamentos aguardando sync`;
      el.classList.remove("d-none");
    } else {
      el.classList.add("d-none");
    }
  }

  function showOfflineSavedPopup() {
    const msg = "Salvo no dispositivo. Será enviado quando houver internet.";
    setBanner(msg, "ok");
    if (typeof showPopup === "function") {
      try {
        showPopup("success", msg);
        return;
      } catch {
        /* fallback */
      }
    }
    alert(msg);
  }

  function warnIncognitoIfNeeded() {
    if (!navigator.onLine) return;
    const est =
      navigator.storage && navigator.storage.estimate
        ? navigator.storage.estimate().catch(() => null)
        : Promise.resolve(null);
    est.then((info) => {
      if (info && info.quota != null && info.quota < 120 * 1024 * 1024) {
        setBanner(
          "Modo offline funciona melhor em aba normal do navegador (evite anônima).",
          "warn"
        );
      }
    }).catch(() => {});
  }

  function installPrepareButton(onPrepare, isEnabled) {
    const btn = document.getElementById("btn-offline-prepare");
    if (!btn) return;

    const show = () => {
      if (isEnabled()) btn.classList.remove("d-none");
      else btn.classList.add("d-none");
    };
    show();

    btn.addEventListener("click", async (e) => {
      e.preventDefault();
      if (!navigator.onLine) {
        setBanner("Conecte-se à internet para baixar os dados para offline.", "warn");
        return;
      }
      if (btn.classList.contains("is-busy")) return;
      btn.classList.add("is-busy");
      try {
        await onPrepare();
      } finally {
        btn.classList.remove("is-busy");
      }
    });

    window.addEventListener("online", show);
    window.addEventListener("offline", show);
  }

  function blockRelatoriosPage() {
    if (!document.body.classList.contains("page-relatorios")) return;
    if (navigator.onLine) return;
    setBanner("Relatórios exigem conexão com a internet.", "warn");
    document.querySelectorAll(".card-relatorio, .rel-form-form, form[action*='relatorio']").forEach((el) => {
      el.style.pointerEvents = "none";
      el.style.opacity = "0.45";
    });
  }

  return {
    setBanner,
    hideBanner,
    updateBadge,
    showOfflineSavedPopup,
    blockRelatoriosPage,
    warnIncognitoIfNeeded,
    installPrepareButton,
  };
})();

window.OfflineUI = OfflineUI;
