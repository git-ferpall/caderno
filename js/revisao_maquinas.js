document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-revisao");
  const custo = document.getElementById("custo");
  const aviso = document.getElementById("aviso-status");

  // Aviso dinâmico de status
  if (custo) {
    const atualizarAviso = () => {
      const val = custo.value.trim();
      if (val === "" || parseFloat(val) === 0) {
        aviso.textContent = "⚠ Deixe o campo vazio ou zero para manter o apontamento PENDENTE.";
        aviso.style.color = "orange";
      } else {
        aviso.textContent = "✔ Com valor informado, o status será CONCLUÍDO.";
        aviso.style.color = "green";
      }
    };
    atualizarAviso();
    custo.addEventListener("input", atualizarAviso);
  }

  // Envio do formulário
  if (form) {
    form.addEventListener("submit", async e => {
      e.preventDefault();
      const dados = new FormData(form);

      try {
        const resp = await fetch("../funcoes/salvar_revisao_maquinas.php", {
          method: "POST",
          body: dados
        });
        const res = await resp.json();

        if (res.ok) {
          showPopup("sucesso", res.msg);
          form.reset();
        } else {
          showPopup("erro", res.msg || "Erro ao salvar apontamento.");
        }
      } catch (err) {
        console.error(err);
        showPopup("erro", "Erro inesperado ao salvar revisão.");
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
  if (btnOk) {
    btnOk.onclick = () => {
      overlay.classList.add("d-none");
      popup.classList.add("d-none");
    };
  }
}
