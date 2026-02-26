document.addEventListener("DOMContentLoaded", () => {

  // === Adicionar nova fonte ===
  const btnAddFonte = document.querySelector(".add-fonte");
  if (btnAddFonte) {
    btnAddFonte.addEventListener("click", () => {
      const lista = document.getElementById("lista-fontes");
      const original = lista.querySelector("select");
      if (!original) return;

      const novo = original.cloneNode(true);
      novo.value = "";
      novo.name = "fonte[]";

      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-fonte";
      wrapper.appendChild(novo);

      lista.appendChild(wrapper);
    });
  }

  // === Submit principal ===
  // === Submit principal ===
  const form = document.getElementById("form-controle-agua");
  if (form) {
    form.addEventListener("submit", e => {
      e.preventDefault();
      const dados = new FormData(form);

      fetch("../funcoes/salvar_controle_agua.php", {
        method: "POST",
        body: dados
      })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            showPopup("success", res.msg || "Controle de água registrado com sucesso!");

            setTimeout(() => {
              window.location.href = "apontamento.php";
            }, 1200);

          } else {
            showPopup("failed", res.err || "Erro ao salvar controle de água.");
          }
        })
        .catch(err => {
          showPopup("failed", "Falha na comunicação: " + err);
        });
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
