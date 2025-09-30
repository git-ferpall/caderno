document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-colheita");
  const qtdInput = document.getElementById("quantidade");
  const avisoQtd = document.createElement("small");

  // Inserir aviso logo abaixo do campo quantidade
  if (qtdInput && qtdInput.parentElement) {
    avisoQtd.style.display = "block";
    avisoQtd.style.marginTop = "4px";
    avisoQtd.style.fontSize = "0.9em";
    avisoQtd.style.color = "orange";
    qtdInput.parentElement.appendChild(avisoQtd);

    const atualizarAviso = () => {
      if (qtdInput.value.trim() === "") {
        avisoQtd.textContent =
          "⚠ Para deixar o apontamento com status PENDENTE, mantenha este campo vazio.";
        avisoQtd.style.color = "orange";
      } else {
        avisoQtd.textContent =
          "✔ Com quantidade informada, o status será CONCLUÍDO.";
        avisoQtd.style.color = "green";
      }
    };

    // Atualiza no carregamento e quando o usuário digita
    atualizarAviso();
    qtdInput.addEventListener("input", atualizarAviso);
  }

  // Submit do formulário
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const formData = new FormData(form);

      try {
        const resp = await fetch("../funcoes/salvar_colheita.php", {
          method: "POST",
          body: formData
        });

        const data = await resp.json();

        if (data.ok) {
          showPopup("sucesso", data.msg);
          form.reset();
          if (qtdInput) qtdInput.dispatchEvent(new Event("input")); // força atualizar aviso
        } else {
          showPopup("erro", data.msg);
        }
      } catch (err) {
        showPopup("erro", "Erro inesperado ao salvar colheita.");
      }
    });
  }
});

/**
 * Exibe popup padrão do sistema
 * @param {"sucesso"|"erro"|"aviso"} tipo
 * @param {string} mensagem
 */
function showPopup(tipo, mensagem) {
  const popup = document.getElementById("popup-msg");
  const popupText = document.getElementById("popup-text");

  if (popup && popupText) {
    popup.className = "popup " + tipo; // aplica classe de cor
    popupText.textContent = mensagem;
    popup.style.display = "block";

    // Fecha automaticamente em 4s
    setTimeout(() => {
      popup.style.display = "none";
    }, 4000);
  } else {
    // fallback caso popup não exista
    alert(mensagem);
  }
}
