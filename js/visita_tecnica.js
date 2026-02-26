document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-visita");
  const conclusao = document.getElementById("conclusao");
  const aviso = document.getElementById("aviso-status");

  // Aviso dinâmico de status
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

  // Adicionar áreas dinamicamente
  const btnAdd = document.querySelector(".add-area");
  if (btnAdd) {
    btnAdd.addEventListener("click", () => {
      const lista = document.getElementById("lista-areas");
      const original = lista.querySelector("select");
      const novo = original.cloneNode(true);
      novo.value = "";
      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-area";
      wrapper.appendChild(novo);
      lista.appendChild(wrapper);
    });
  }

  // Envio
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const dados = new FormData(form);

      try {
        const resp = await fetch("../funcoes/salvar_visita_tecnica.php", {
          method: "POST",
          body: dados,
        });

        const data = await resp.json();

        if (data.ok) {
          showPopup("success", data.msg || "Visita técnica salva com sucesso!");

          setTimeout(() => {
            window.location.href = "/apontamento.php";
          }, 1200);

        } else {
          showPopup("failed", data.msg || "Erro ao salvar visita.");
        }

      } catch (err) {
        showPopup("failed", "Erro inesperado ao salvar visita.");
      }
    });
  }

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
