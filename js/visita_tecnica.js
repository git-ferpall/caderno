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
            window.location.href = "apontamento.php";
          }, 1200);

        } else {
          showPopup("failed", data.msg || "Erro ao salvar visita.");
        }

      } catch (err) {
        showPopup("failed", "Erro inesperado ao salvar visita.");
      }
    });
  }
});

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
