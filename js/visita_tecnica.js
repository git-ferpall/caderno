document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-visita");
  const conclusao = document.getElementById("conclusao");
  const aviso = document.getElementById("aviso-status");

  // Aviso dinâmico
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
}); // ✅ AGORA FECHA O DOMContentLoaded


// ✅ Função fora do DOMContentLoaded
function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");

  const popup = tipo === "success" ? popupSuccess : popupFailed;

  if (overlay && popup) {
    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");

    const msgBox =
      popup.querySelector(".popup-text") ||
      popup.querySelector(".popup-title");

    if (msgBox) msgBox.textContent = mensagem;
  } else {
    alert(mensagem);
  }
}