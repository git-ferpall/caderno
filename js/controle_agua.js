document.addEventListener("DOMContentLoaded", () => {

  /* ===============================
  ADICIONAR FONTE
  =============================== */

  const btnAddFonte = document.querySelector(".add-fonte");

  if (btnAddFonte) {

    btnAddFonte.addEventListener("click", () => {

      const lista = document.getElementById("lista-fontes");
      const original = lista.querySelector(".fonte-select");

      if (!original) return;

      const novo = original.cloneNode(true);

      novo.value = "";
      novo.name = "fonte[]";

      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-fonte linha";

      const btnRemover = document.createElement("button");
      btnRemover.type = "button";
      btnRemover.className = "remove-btn";
      btnRemover.innerHTML = "−";

      btnRemover.onclick = () => {

        const total = document.querySelectorAll("#lista-fontes .form-box-fonte").length;

        if (total > 1) {
          wrapper.remove();
        } else {
          alert("É necessário manter pelo menos uma fonte.");
        }

      };

      wrapper.appendChild(novo);
      wrapper.appendChild(btnRemover);

      lista.appendChild(wrapper);

    });

  }

  // === Submit principal ===
  // === Submit principal ===
  const form = document.getElementById("form-controle-agua");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      if (typeof CadernoSalvar !== "undefined") {
        CadernoSalvar.submitForm(form, "salvar_controle_agua.php");
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
