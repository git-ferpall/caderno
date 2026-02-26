document.addEventListener("DOMContentLoaded", () => {

  // === Função para carregar ÁREAS em todos os selects ===
  function carregarAreas() {
    fetch("../funcoes/buscar_areas.php")
      .then(r => r.json())
      .then(data => {
        document.querySelectorAll(".area-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione a área</option>';
          data.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = `${item.nome} (${item.tipo})`;
            sel.appendChild(opt);
          });
        });
      })
      .catch(err => console.error("Erro ao carregar áreas:", err));
  }

  // === Função para carregar PRODUTOS em todos os selects ===
  function carregarProdutos() {
    fetch("../funcoes/buscar_produtos.php")
      .then(r => r.json())
      .then(data => {
        document.querySelectorAll(".produto-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione o produto</option>';
          data.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = item.nome;
            sel.appendChild(opt);
          });
        });
      })
      .catch(err => console.error("Erro ao carregar produtos:", err));
  }

  // === Botão adicionar ÁREA ===
  document.querySelector(".add-area").addEventListener("click", () => {
    const lista = document.getElementById("lista-areas");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);

    novo.value = "";
    novo.removeAttribute("id");
    novo.name = "area[]";
    novo.classList.add("area-select");

    const wrapper = document.createElement("div");
    wrapper.className = "form-box form-box-area";
    wrapper.appendChild(novo);

    lista.appendChild(wrapper);
    carregarAreas(); // popula o novo select
  });

  // === Botão adicionar PRODUTO ===
  document.querySelector(".add-produto").addEventListener("click", () => {
    const lista = document.getElementById("lista-produtos");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);

    novo.value = "";
    novo.removeAttribute("id");
    novo.name = "produto[]";
    novo.classList.add("produto-select");

    const wrapper = document.createElement("div");
    wrapper.className = "form-box form-box-produto";
    wrapper.appendChild(novo);

    lista.appendChild(wrapper);
    carregarProdutos(); // popula o novo select
  });

  // === Carregar selects iniciais ===
  carregarAreas();
  carregarProdutos();

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

            // Pequeno delay só para o usuário ver o popup
            setTimeout(() => {
              window.location.href = "apontamento.php";
            }, 1200);
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
