document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-coleta");
  const resultado = document.getElementById("resultado");
  const aviso = document.getElementById("aviso-status");

  // === Atualiza aviso de status ===
  if (resultado) {
    const atualizarAviso = () => {
      const val = resultado.value.trim();
      if (val === "") {
        aviso.textContent = "⚠ Sem resultado informado, o status ficará PENDENTE.";
        aviso.style.color = "orange";
      } else {
        aviso.textContent = "✔ Resultado informado — status será CONCLUÍDO.";
        aviso.style.color = "green";
      }
    };
    atualizarAviso();
    resultado.addEventListener("input", atualizarAviso);
  }

  // === Botão adicionar área ===
  const btnAdd = document.querySelector(".add-area");
  if (btnAdd) {
    btnAdd.addEventListener("click", () => {
      const lista = document.getElementById("lista-areas");
      const original = lista.querySelector("select");
      const novo = original.cloneNode(true);
      novo.value = "";
      novo.name = "area[]";
      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-area";
      wrapper.appendChild(novo);
      lista.appendChild(wrapper);
    });
  }

  // === Envio ===
  if (form) {
    form.addEventListener("submit", async e => {
      e.preventDefault();
      const dados = new FormData(form);

      try {
        const resp = await fetch("../funcoes/salvar_coleta_analise.php", {
          method: "POST",
          body: dados
        });
        const res = await resp.json();

        if (res.ok) {
          showPopup("sucesso", res.msg);
          form.reset();
        } else {
          showPopup("erro", res.msg || "Erro ao salvar dados.");
        }
      } catch (err) {
        showPopup("erro", "Erro inesperado ao salvar coleta.");
      }
    });
  }
});

function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");
  const popup = (tipo === "sucesso") ? popupSuccess : popupFailed;

  overlay.classList.remove("d-none");
  popup.classList.remove("d-none");
  const msgBox = popup.querySelector(".popup-text") || popup.querySelector(".popup-title");
  msgBox.textContent = mensagem;

  const btnOk = popup.querySelector(".popup-btn");
  if (btnOk) btnOk.onclick = () => {
    overlay.classList.add("d-none");
    popup.classList.add("d-none");
  };
}
