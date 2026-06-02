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
    if (typeof showPopup === "function") {
      showPopup("success", "Salvo no dispositivo. Será enviado quando houver internet.");
      return;
    }
    alert("Salvo no dispositivo. Será enviado quando houver internet.");
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

  return { setBanner, hideBanner, updateBadge, showOfflineSavedPopup, blockRelatoriosPage };
})();

window.OfflineUI = OfflineUI;
