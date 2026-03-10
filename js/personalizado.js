document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-personalizado");
  const conclusao = document.getElementById("conclusao");
  const aviso = document.getElementById("aviso-status");

  // Atualiza o aviso de status
  if (conclusao) {
    const atualizarAviso = () => {
      if (conclusao.value.trim() === "") {
        aviso.textContent = "⚠ Se este campo ficar vazio, o status será PENDENTE.";
        aviso.style.color = "orange";
      } else {
        aviso.textContent = "✔ Com conclusão informada, o status será CONCLUÍDO.";
        aviso.style.color = "green";
      }
    };
    atualizarAviso();
    conclusao.addEventListener("input", atualizarAviso);
  }

  /* ===============================
  ADICIONAR ÁREA
  =============================== */

  const btnAdd = document.querySelector(".add-area");

  if (btnAdd) {

    btnAdd.addEventListener("click", () => {

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

  // Envio do formulário
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const dados = new FormData(form);

      try {
        const resp = await fetch("../funcoes/salvar_personalizado.php", {
          method: "POST",
          body: dados,
        });
        const data = await resp.json();
        if (data.ok) {
          showPopup("success", data.msg || "Dados salvos com sucesso!");

          setTimeout(() => {
            window.location.href = "apontamento.php";
          }, 1200);
        } else {
          showPopup("erro", data.msg);
        }
      } catch (err) {
        showPopup("erro", "Erro inesperado ao salvar apontamento.");
      }
    });
  }
});

function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");
  const popup = tipo === "sucesso" ? popupSuccess : popupFailed;

  if (overlay && popup) {
    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");
    const msgBox = popup.querySelector(".popup-text") || popup.querySelector(".popup-title");
    if (msgBox) msgBox.textContent = mensagem;
    const btnOk = popup.querySelector(".popup-btn");
    if (btnOk) btnOk.onclick = () => {
      overlay.classList.add("d-none");
      popup.classList.add("d-none");
    };
  } else {
    alert(mensagem);
  }
}
