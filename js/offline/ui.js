const OfflineUI = (() => {
  let banner = null;
  let badge = null;

  const SUCCESS_COPY = {
    online: {
      title: "Dados salvos com sucesso!",
      subtitle: "Seu apontamento foi registrado no sistema.",
    },
    offline: {
      title: "Apontamento salvo com sucesso!",
      subtitle:
        "Os dados ficaram guardados neste aparelho e serão enviados automaticamente quando a internet voltar.",
    },
  };

  function hideOverlayPopups() {
    document.querySelectorAll("#popup-overlay .popup-box").forEach((el) => el.classList.add("d-none"));
  }

  function ensureSuccessSubtitle(popup) {
    let sub = popup.querySelector(".popup-text");
    if (!sub) {
      sub = document.createElement("p");
      sub.className = "popup-text";
      popup.querySelector(".popup-title")?.insertAdjacentElement("afterend", sub);
    }
    return sub;
  }

  /** Popup verde padrão do sistema (#popup-success). mode: "online" | "offline" */
  function showSuccessPopup(mode = "online", onOk) {
    const copy = SUCCESS_COPY[mode] || SUCCESS_COPY.online;
    const overlay = document.getElementById("popup-overlay");
    const popup = document.getElementById("popup-success");

    if (!overlay || !popup) {
      alert(copy.title + (copy.subtitle ? "\n\n" + copy.subtitle : ""));
      if (typeof onOk === "function") onOk();
      return;
    }

    hideOverlayPopups();
    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");

    const titleEl = popup.querySelector(".popup-title");
    if (titleEl) titleEl.textContent = copy.title;

    const subEl = ensureSuccessSubtitle(popup);
    if (copy.subtitle) {
      subEl.textContent = copy.subtitle;
      subEl.classList.remove("d-none");
    } else {
      subEl.textContent = "";
      subEl.classList.add("d-none");
    }

    const btnOk = document.getElementById("btn-ok") || popup.querySelector(".popup-btn");
    if (btnOk) {
      btnOk.onclick = () => {
        if (typeof closePopup === "function") closePopup();
        else {
          popup.classList.add("d-none");
          overlay.classList.add("d-none");
        }
        if (typeof onOk === "function") onOk();
      };
    }
  }

  function showFailedPopup(message, title) {
    const overlay = document.getElementById("popup-overlay");
    const popup = document.getElementById("popup-failed");

    if (!overlay || !popup) {
      alert(message || "Não foi possível salvar.");
      return;
    }

    hideOverlayPopups();
    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");

    const titleEl = popup.querySelector(".popup-title");
    if (titleEl && title) titleEl.textContent = title;

    const textEl = popup.querySelector(".popup-text");
    if (textEl) textEl.textContent = message;

    const btn = popup.querySelector(".popup-btn");
    if (btn) {
      btn.onclick = () => {
        if (typeof closePopup === "function") closePopup();
        else {
          popup.classList.add("d-none");
          overlay.classList.add("d-none");
        }
      };
    }
  }

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

  function showOfflineSavedPopup(onOk) {
    setBanner("Apontamento salvo neste aparelho.", "ok");
    showSuccessPopup("offline", onOk);
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
    showSuccessPopup,
    showFailedPopup,
    showOfflineSavedPopup,
    blockRelatoriosPage,
    warnIncognitoIfNeeded,
    installPrepareButton,
  };
})();

window.OfflineUI = OfflineUI;
