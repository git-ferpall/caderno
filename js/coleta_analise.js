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

  // === Botão adicionar área ===
  const btnAddArea = document.querySelector(".add-area");
  if (btnAddArea) {
    btnAddArea.addEventListener("click", () => {
      const lista = document.getElementById("lista-areas");
      const original = lista.querySelector("select");
      if (!original) return;

      const novo = original.cloneNode(true);
      novo.value = "";
      novo.name = "area[]";
      novo.classList.add("area-select");

      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-area";
      wrapper.appendChild(novo);

      lista.appendChild(wrapper);
    });
  }

  // === Submit do formulário ===
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const dados = new FormData(form);

      try {
        const resp = await fetch("../funcoes/salvar_coleta_analise.php", {
          method: "POST",
          body: dados,
        });

        // se a resposta não for JSON válida, força leitura de texto
        const text = await resp.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch {
          console.error("Resposta inesperada:", text);
          throw new Error("Resposta inválida do servidor");
        }

        if (data.ok) {
          showPopup("success", data.msg || "Dados Salvos com Sucesso");

          setTimeout(() => {
            window.location.href = "apontamento.php";
          }, 1200);
        } else {
          showPopup("erro", data.msg);
        }
      } catch (err) {
        showPopup("erro", "Erro inesperado ao salvar coleta.");
      }
    });
  }
});

// === Popup padrão ===
function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");

  let popup = (tipo === "sucesso") ? popupSuccess : popupFailed;

  if (overlay && popup) {
    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");

    const msgBox = popup.querySelector(".popup-text") || popup.querySelector(".popup-title");
    if (msgBox) msgBox.textContent = mensagem;

    const btnOk = popup.querySelector(".popup-btn");
    if (btnOk) {
      btnOk.onclick = () => {
        overlay.classList.add("d-none");
        popup.classList.add("d-none");
      };
    }
  } else {
    alert(mensagem);
  }
}
