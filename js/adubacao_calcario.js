document.addEventListener("DOMContentLoaded", () => {

  // === Função para exibir popup de feedback ===
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

  // === Carregar ÁREAS ===
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
  carregarAreas();

  // === Botão adicionar ÁREA ===
  const btnAddArea = document.querySelector(".add-area");
  if (btnAddArea) {
    btnAddArea.addEventListener("click", () => {
      const lista = document.getElementById("lista-areas");
      const original = lista.querySelector("select");
      if (!original) return;

      const novo = original.cloneNode(true);
      novo.value = "";
      novo.name = "area[]";
      novo.classList.add("area-select");

      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-area";
      wrapper.appendChild(novo);

      lista.appendChild(wrapper);
      carregarAreas();
    });
  }

  // === Carregar PRODUTOS ===
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
  carregarProdutos();

  // === Botão adicionar PRODUTO ===
  const btnAddProduto = document.querySelector(".add-produto");
  if (btnAddProduto) {
    btnAddProduto.addEventListener("click", () => {
      const lista = document.getElementById("lista-produtos");
      const original = lista.querySelector("select");
      if (!original) return;

      const novo = original.cloneNode(true);
      novo.value = "";
      novo.name = "produto[]";
      novo.classList.add("produto-select");

      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-produto";
      wrapper.appendChild(novo);

      lista.appendChild(wrapper);
      carregarProdutos();
    });
  }

  // === Envio do formulário principal ===
  const form = document.getElementById("form-adubacao-calcario");
  if (form) {
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
            showPopup("success", res.msg || "Dados salvos com sucesso!");

            setTimeout(() => {
              window.location.href = "apontamento.php";
            }, 1200);
          } else {
            showPopup("failed", res.err || "Erro ao salvar adubação.");
          }
        })
        .catch(err => {
          console.error(err);
          showPopup("failed", "Falha na comunicação com o servidor.");
        });
    });
  }
});

// === Função genérica para abrir popups ===
function abrirPopup(id) {
  const overlay = document.getElementById("popup-overlay");
  const popup = document.getElementById(id);
  if (overlay && popup) {
    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");
  }
}
