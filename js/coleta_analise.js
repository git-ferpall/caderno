document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-coleta");
  const resultadoInput = document.getElementById("resultado");
  const aviso = document.getElementById("aviso-status");

  // === Atualiza aviso conforme resultado ===
  if (resultadoInput) {
    const atualizarAviso = () => {
      if (resultadoInput.value.trim() === "") {
        aviso.textContent = "⚠ Se o resultado for informado, o status será CONCLUÍDO.";
        aviso.style.color = "orange";
      } else {
        aviso.textContent = "✔ Com resultado informado, o status será CONCLUÍDO.";
        aviso.style.color = "green";
      }
    };
    atualizarAviso();
    resultadoInput.addEventListener("input", atualizarAviso);
  }

  /* ===============================
  ADICIONAR ÁREA
  =============================== */

  const btnAddArea = document.querySelector(".add-area");

  if (btnAddArea) {

    btnAddArea.addEventListener("click", () => {

      const lista = document.getElementById("lista-areas");
      const original = lista.querySelector(".form-box-area");

      if (!original) return;

      const clone = original.cloneNode(true);
      const select = clone.querySelector("select");

      select.value = "";

      const btnRemover = document.createElement("button");
      btnRemover.type = "button";
      btnRemover.className = "remove-btn";
      btnRemover.innerHTML = "−";

      btnRemover.onclick = () => {

        const total = document.querySelectorAll("#lista-areas .form-box-area").length;

        if (total > 1) {
          clone.remove();
        } else {
          alert("É necessário manter pelo menos uma área.");
        }

      };

      clone.appendChild(btnRemover);

      lista.appendChild(clone);

    });

  }

  // === Submit do formulário ===
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      if (typeof CadernoSalvar !== "undefined") {
        CadernoSalvar.submitForm(form, "salvar_coleta_analise.php");
      }
    });
  }
});

// === Popup padrão ===
function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");

  document.querySelectorAll(".popup-box").forEach(p => p.classList.add("d-none"));
  overlay?.classList.remove("d-none");

  if (tipo === "success") {
    popupSuccess?.classList.remove("d-none");
    popupSuccess.querySelector(".popup-title").textContent = mensagem;
  } else {
    popupFailed?.classList.remove("d-none");
    popupFailed.querySelector(".popup-title").textContent = mensagem;
  }
}