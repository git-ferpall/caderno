document.addEventListener("DOMContentLoaded", () => {
  // === Carregar ÁREAS ===
  fetch("../funcoes/buscar_areas.php")
    .then(r => r.json())
    .then(data => {
      // Função para popular um select
      function populaSelect(select) {
        select.innerHTML = '<option value="">Selecione a área</option>';
        data.forEach(item => {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = `${item.nome} (${item.tipo})`;
          select.appendChild(opt);
        });
      }

      // Popula o primeiro select
      document.querySelectorAll(".area-select").forEach(populaSelect);

      // Botão adicionar nova área
      document.querySelector(".add-area").addEventListener("click", () => {
        const lista = document.getElementById("lista-areas");

        const div = document.createElement("div");
        div.className = "form-box form-box-area";

        const sel = document.createElement("select");
        sel.name = "area[]";
        sel.className = "form-select form-text area-select";

        // popula as opções
        data.forEach(item => {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = `${item.nome} (${item.tipo})`;
          sel.appendChild(opt);
        });

        div.appendChild(sel);
        lista.appendChild(div);
      });


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

  // === Botões adicionar (futuro modal de cadastro rápido) ===
  document.querySelector(".add-area").addEventListener("click", () => {
    console.log("Botão Adicionar Área clicado");
  });

  document.querySelector(".add-produto").addEventListener("click", () => {
    console.log("Botão Adicionar Produto clicado");
  });

  // === Submit do formulário ===
  const form = document.getElementById("form-plantio");
  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const overlay = document.getElementById("popup-overlay");
    let popupConfirm = document.getElementById("popup-confirm-plantio");

    if (!popupConfirm) {
      popupConfirm = document.createElement("div");
      popupConfirm.className = "popup-box d-none";
      popupConfirm.id = "popup-confirm-plantio";
      popupConfirm.innerHTML = `
        <h2 class="popup-title">Gerar também colheita?</h2>
        <p class="popup-text">
          Deseja que seja criado automaticamente um apontamento de <b>colheita</b> 
          com status <b>PENDENTE</b> para este plantio?
        </p>
        <div class="popup-actions">
          <button class="popup-btn fundo-cinza-b cor-preto" id="btn-no">Não</button>
          <button class="popup-btn fundo-verde" id="btn-yes">Sim</button>
        </div>
      `;
      overlay.appendChild(popupConfirm);
    }

    // Oculta outros popups
    document.querySelectorAll(".popup-box").forEach(p => p.classList.add("d-none"));
    overlay.classList.remove("d-none");
    popupConfirm.classList.remove("d-none");

    const enviarFormulario = (incluir_colheita) => {
      const dados = new FormData(form);
      dados.append("incluir_colheita", incluir_colheita ? "1" : "0");

      fetch("../funcoes/salvar_plantio.php", {
        method: "POST",
        body: dados
      })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            showPopup("success", res.msg || "Plantio salvo com sucesso!");
            form.reset();
          } else {
            showPopup("failed", res.err || "Erro ao salvar o plantio.");
          }
        })
        .catch(err => {
          showPopup("failed", "Falha na comunicação: " + err);
        });
    };

    popupConfirm.querySelector("#btn-yes").onclick = () => enviarFormulario(true);
    popupConfirm.querySelector("#btn-no").onclick = () => enviarFormulario(false);
  });
});

// === Função padrão de popup ===
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
