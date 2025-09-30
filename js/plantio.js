document.addEventListener("DOMContentLoaded", () => {
  // === Carregar ÁREAS ===
  fetch("../funcoes/buscar_areas.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("area");
      sel.innerHTML = '<option value="">Selecione a área</option>'; // reseta
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = `${item.nome} (${item.tipo})`; // exibe nome + tipo
        sel.appendChild(opt);
      });
    })
    .catch(err => console.error("Erro ao carregar áreas:", err));

  // === Carregar PRODUTOS ===
  fetch("../funcoes/buscar_produtos.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("produto");
      sel.innerHTML = '<option value="">Selecione o produto</option>'; // reseta
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome;
        sel.appendChild(opt);
      });
    })
    .catch(err => console.error("Erro ao carregar produtos:", err));

  // === Prevenir duplicação ===
  ["area", "produto"].forEach(id => {
    const select = document.getElementById(id);
    select.addEventListener("change", e => {
      const val = e.target.value;
      // reseta
      document.querySelectorAll(`#${id} option`).forEach(opt => {
        opt.disabled = false;
      });
      // desabilita selecionado em outros selects iguais (se houver mais selects no futuro)
      if (val) {
        document.querySelectorAll(`#${id} option[value='${val}']`).forEach(opt => {
          if (opt.parentElement !== e.target) {
            opt.disabled = true;
          }
        });
      }
    });
  });

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

    // Popup de confirmação
    const overlay = document.getElementById("popup-overlay");
    const popupConfirm = document.createElement("div");
    popupConfirm.className = "popup-box";
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

    overlay.innerHTML = ""; // limpa
    overlay.appendChild(popupConfirm);
    overlay.classList.remove("d-none");

    const enviarFormulario = (incluir_colheita) => {
      const dados = new FormData(form);
      dados.append("incluir_colheita", incluir_colheita ? "1" : "0");

      fetch("../funcoes/salvar_plantio.php", {
        method: "POST",
        body: dados
      })
        .then(r => r.json())
        .then(res => {
          overlay.classList.add("d-none");
          if (res.ok) {
            showPopup("success", res.msg || "Plantio salvo com sucesso!");
            form.reset();
          } else {
            showPopup("failed", res.err || "Erro ao salvar o plantio.");
          }
        })
        .catch(err => {
          overlay.classList.add("d-none");
          showPopup("failed", "Falha na comunicação: " + err);
        });
    };

    popupConfirm.querySelector("#btn-yes").addEventListener("click", () => {
      enviarFormulario(true);
    });

    popupConfirm.querySelector("#btn-no").addEventListener("click", () => {
      enviarFormulario(false);
    });
  });
});

// === Função para usar os popups padrões do sistema ===
function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");

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
