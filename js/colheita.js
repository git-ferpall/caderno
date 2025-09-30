document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-colheita");
  const qtdInput = document.getElementById("quantidade");
  const avisoQtd = document.createElement("small");

  // === Aviso abaixo do campo quantidade ===
  if (qtdInput && qtdInput.parentElement) {
    avisoQtd.style.display = "block";
    avisoQtd.style.marginTop = "4px";
    avisoQtd.style.fontSize = "0.9em";
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

    atualizarAviso();
    qtdInput.addEventListener("input", atualizarAviso);
  }

  // === Carregar áreas ===
  fetch("../funcoes/buscar_areas.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("area");
      if (!sel) return;
      sel.innerHTML = '<option value="">Selecione a área</option>';
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome; // usa campo "nome" da tabela areas
        sel.appendChild(opt);
      });
    })
    .catch(err => console.error("Erro ao carregar áreas:", err));

  // === Carregar produtos ===
  fetch("../funcoes/buscar_produtos.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("produto");
      if (!sel) return;
      sel.innerHTML = '<option value="">Selecione o produto</option>';
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome;
        sel.appendChild(opt);
      });
    })
    .catch(err => console.error("Erro ao carregar produtos:", err));

  // === Submit do formulário ===
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
          if (qtdInput) qtdInput.dispatchEvent(new Event("input"));
        } else {
          showPopup("erro", data.msg);
        }
      } catch (err) {
        showPopup("erro", "Erro inesperado ao salvar colheita.");
      }
    });
  }
});

function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("overlay");
  const popupSuccess = document.getElementById("popupSuccess");
  const popupFailed = document.getElementById("popupFailed");

  let popup;
  if (tipo === "sucesso") {
    popup = popupSuccess;
  } else {
    popup = popupFailed;
  }

  if (overlay && popup) {
    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");

    const msgBox = popup.querySelector(".popup-text");
    if (msgBox) msgBox.textContent = mensagem;
  } else {
    alert(mensagem); // fallback só se não existir o popup padrão
  }
}

