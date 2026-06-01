// Popups padrão do sistema — Silo de Dados

function siloOverlay() {
  return document.getElementById("popup-overlay");
}

function siloOcultarPopups() {
  document.querySelectorAll("#popup-overlay .popup-box").forEach((el) => {
    el.classList.add("d-none");
  });
}

function siloShowSuccess(mensagem, onOk) {
  siloOcultarPopups();
  const overlay = siloOverlay();
  const popup = document.getElementById("popup-success");
  if (!overlay || !popup) return;

  overlay.classList.remove("d-none");
  popup.classList.remove("d-none");
  const title = popup.querySelector(".popup-title");
  if (title) title.textContent = mensagem;

  const btn = popup.querySelector(".popup-btn");
  if (btn) {
    btn.onclick = () => {
      popup.classList.add("d-none");
      if (typeof onOk === "function") onOk();
      if (typeof closePopup === "function") closePopup();
    };
  }
}

function siloShowError(mensagem, onOk) {
  siloOcultarPopups();
  const overlay = siloOverlay();
  const popup = document.getElementById("popup-failed");
  if (!overlay || !popup) return;

  overlay.classList.remove("d-none");
  popup.classList.remove("d-none");
  const text = popup.querySelector(".popup-text");
  if (text) text.textContent = mensagem;

  const btn = popup.querySelector(".popup-btn");
  if (btn) {
    btn.onclick = () => {
      popup.classList.add("d-none");
      if (typeof onOk === "function") onOk();
      if (typeof closePopup === "function") closePopup();
    };
  }
}

function siloConfirm({ title, text, onConfirm }) {
  siloOcultarPopups();
  const overlay = siloOverlay();
  const popup = document.getElementById("popup-delete");
  const btnConfirm = document.getElementById("confirm-delete");
  if (!overlay || !popup || !btnConfirm) return;

  const titleEl = popup.querySelector(".popup-title");
  const textEl = popup.querySelector(".popup-text");
  if (titleEl) titleEl.textContent = title || "Confirmar exclusão?";
  if (textEl) textEl.textContent = text || "Esta ação não poderá ser desfeita.";

  overlay.classList.remove("d-none");
  popup.classList.remove("d-none");

  btnConfirm.onclick = async () => {
    popup.classList.add("d-none");
    if (typeof onConfirm === "function") await onConfirm();
    if (typeof closePopup === "function") closePopup();
  };
}

function siloPrompt({ title, label, defaultValue = "" }) {
  return new Promise((resolve) => {
    siloOcultarPopups();
    const overlay = siloOverlay();
    const popup = document.getElementById("popup-silo-input");
    const field = document.getElementById("popup-silo-input-field");
    const btnOk = document.getElementById("popup-silo-input-confirm");
    const btnCancel = document.getElementById("popup-silo-input-cancel");
    if (!overlay || !popup || !field || !btnOk || !btnCancel) {
      resolve(null);
      return;
    }

    const titleEl = document.getElementById("popup-silo-input-title");
    const labelEl = document.getElementById("popup-silo-input-label");
    if (titleEl) titleEl.textContent = title || "Informe o nome";
    if (labelEl) labelEl.textContent = label || "Nome";
    field.value = defaultValue || "";

    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");
    setTimeout(() => field.focus(), 50);

    const cleanup = (valor) => {
      popup.classList.add("d-none");
      btnOk.onclick = null;
      btnCancel.onclick = null;
      field.onkeydown = null;
      resolve(valor);
    };

    btnOk.onclick = () => {
      const val = field.value.trim();
      if (!val) return;
      cleanup(val);
    };

    btnCancel.onclick = () => {
      cleanup(null);
      if (typeof closePopup === "function") closePopup();
    };

    field.onkeydown = (e) => {
      if (e.key === "Enter") btnOk.click();
      if (e.key === "Escape") btnCancel.click();
    };
  });
}

window.siloShowSuccess = siloShowSuccess;
window.siloShowError = siloShowError;
window.siloConfirm = siloConfirm;
window.siloPrompt = siloPrompt;
