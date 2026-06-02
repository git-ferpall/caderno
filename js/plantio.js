document.addEventListener("DOMContentLoaded", () => {

  // === Função para carregar ÁREAS em todos os selects ===
  function carregarAreas(select = null) {

  fetch("/funcoes/buscar_areas.php", { credentials: "same-origin" })
    .then(r => r.json())
    .then(data => {
      if (!Array.isArray(data)) return;

      const selects = select ? [select] : document.querySelectorAll(".area-select");

      selects.forEach(sel => {

        const valorAtual = sel.value; // guarda seleção atual

        sel.innerHTML = '<option value="">Selecione a área</option>';

        data.forEach(item => {

          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = `${item.nome} (${item.tipo})`;

          if (item.id == valorAtual) {
            opt.selected = true;
          }

          sel.appendChild(opt);

        });

      });

    })
    .catch(err => console.error("Erro ao carregar áreas:", err));

}

  // === Função para carregar PRODUTOS em todos os selects ===
  function carregarProdutos(select = null) {

    fetch("/funcoes/buscar_produtos.php", { credentials: "same-origin" })
      .then(r => r.json())
      .then(data => {
        if (!Array.isArray(data)) return;

        const selects = select ? [select] : document.querySelectorAll(".produto-select");

        selects.forEach(sel => {

          const valorAtual = sel.value;

          sel.innerHTML = '<option value="">Selecione o produto</option>';

          data.forEach(item => {

            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = item.nome;

            if (item.id == valorAtual) {
              opt.selected = true;
            }

            sel.appendChild(opt);

          });

        });

      })
      .catch(err => console.error("Erro ao carregar produtos:", err));

  }

  /* ===============================
  ADICIONAR ÁREA
  =============================== */
  document.querySelector(".add-area").addEventListener("click", () => {

  const lista = document.getElementById("lista-areas");
  const original = lista.querySelector("select");
  const novo = original.cloneNode(true);

  novo.value = "";
  novo.removeAttribute("id");
  novo.name = "area[]";
  novo.classList.add("area-select");

  const wrapper = document.createElement("div");
  wrapper.className = "form-box form-box-area linha";

  const btnRemover = document.createElement("button");
  btnRemover.type = "button";
  btnRemover.className = "remove-btn";
  btnRemover.innerHTML = "−";

  btnRemover.onclick = () => {

    const total = document.querySelectorAll(".form-box-area").length;

    if (total > 1) {
      wrapper.remove();
    } else {
      alert("É necessário manter pelo menos uma área.");
    }

  };

  wrapper.appendChild(novo);
  wrapper.appendChild(btnRemover);

  lista.appendChild(wrapper);

  carregarAreas(novo);

});


/* ===============================
ADICIONAR PRODUTO
=============================== */
document.querySelector(".add-produto").addEventListener("click", () => {

  const lista = document.getElementById("lista-produtos");
  const original = lista.querySelector("select");
  const novo = original.cloneNode(true);

  novo.value = "";
  novo.removeAttribute("id");
  novo.name = "produto[]";
  novo.classList.add("produto-select");

  const wrapper = document.createElement("div");
  wrapper.className = "form-box form-box-produto linha";

  const btnRemover = document.createElement("button");
  btnRemover.type = "button";
  btnRemover.className = "remove-btn";
  btnRemover.innerHTML = "−";

  btnRemover.onclick = () => {

    const total = document.querySelectorAll(".form-box-produto").length;

    if (total > 1) {
      wrapper.remove();
    } else {
      alert("É necessário manter pelo menos um produto.");
    }

  };

  wrapper.appendChild(novo);
  wrapper.appendChild(btnRemover);

  lista.appendChild(wrapper);

  carregarProdutos(novo);

});

  // === Carregar selects iniciais ===
  carregarAreas();
  carregarProdutos();

  // Submit: CadernoSalvar (salvar-form.js) trata plantio + popup colheita offline/online.
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
