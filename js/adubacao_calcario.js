document.addEventListener("DOMContentLoaded", () => {

  // === Carregar ÁREAS ===
  fetch("../funcoes/buscar_areas.php")
    .then(r => r.json())
    .then(data => {
      function populaSelect(select) {
        select.innerHTML = '<option value="">Selecione a área</option>';
        data.forEach(item => {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = `${item.nome} (${item.tipo})`;
          select.appendChild(opt);
        });
      }

      document.querySelectorAll(".area-select").forEach(populaSelect);

      // Botão +
      document.querySelector(".add-area").addEventListener("click", () => {
        const lista = document.getElementById("lista-areas");
        const sel = document.createElement("select");
        sel.name = "area[]";
        sel.className = "form-select form-text area-select";
        populaSelect(sel);
        lista.appendChild(sel);
      });
    })
    .catch(err => console.error("Erro ao carregar áreas:", err));

  // === Carregar PRODUTOS ===
  fetch("../funcoes/buscar_produtos.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("produto");
      sel.innerHTML = '<option value="">Selecione o produto</option>';
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome;
        sel.appendChild(opt);
      });
    })
    .catch(err => console.error("Erro ao carregar produtos:", err));

  // === Envio do formulário ===
  const form = document.getElementById("form-adubacao-calcario");
  form.addEventListener("submit", e => {
    e.preventDefault();

    const dados = new FormData(form);

    fetch("../funcoes/salvar_adubacao_calcario.php", {
      method: "POST",
      body: dados
    })
      .then(r => r.json())
      .then(res => {
        if (res.ok) {
          showPopup("success", res.msg || "Apontamento de adubação salvo com sucesso!");
          form.reset();
        } else {
          showPopup("failed", res.err || "Erro ao salvar o apontamento.");
        }
      })
      .catch(err => {
        showPopup("failed", "Falha na comunicação: " + err);
      });
  });
});

// Função popup padrão
function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");
  document.querySelectorAll(".popup-box").forEach(p => p.classList.add("d-none"));
  overlay.classList.remove("d-none");

  if (tipo === "success") {
    popupSuccess.classList.remove("d-none");
    popupSuccess.querySelector(".popup-title").textContent = mensagem;
  } else {
    popupFailed.classList.remove("d-none");
    popupFailed.querySelector(".popup-text").textContent = mensagem;
  }

  setTimeout(() => {
    overlay.classList.add("d-none");
    popupSuccess?.classList.add("d-none");
    popupFailed?.classList.add("d-none");
  }, 4000);
}
