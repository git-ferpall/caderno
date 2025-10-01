document.addEventListener("DOMContentLoaded", () => {
  let areasDisponiveis = [];
  let produtosDisponiveis = [];

  // === Carregar ÁREAS ===
  fetch("../funcoes/buscar_areas.php")
    .then(r => r.json())
    .then(data => {
      areasDisponiveis = data;
      atualizarSelects(".area-select", areasDisponiveis, "Selecione a área");
    });

  // === Carregar PRODUTOS ===
  fetch("../funcoes/buscar_produtos.php")
    .then(r => r.json())
    .then(data => {
      produtosDisponiveis = data;
      atualizarSelects(".produto-select", produtosDisponiveis, "Selecione o produto");
    });

  // === Função para atualizar selects ===
  function atualizarSelects(selector, dados, textoPadrao) {
    document.querySelectorAll(selector).forEach(sel => {
      sel.innerHTML = `<option value="">${textoPadrao}</option>`;
      dados.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome || `${item.nome} (${item.tipo})`;
        sel.appendChild(opt);
      });
    });
  }

  // === Função para criar campo dinamicamente ===
  function criarCampo(tipo) {
    const div = document.createElement("div");
    div.className = `form-box form-box-${tipo}`;

    const sel = document.createElement("select");
    sel.name = `${tipo}[]`;
    sel.className = `form-select form-text ${tipo}-select`;
    sel.required = true;

    const btnRemove = document.createElement("button");
    btnRemove.type = "button";
    btnRemove.className = `remove-btn remove-${tipo}`;
    btnRemove.textContent = "x";

    btnRemove.addEventListener("click", () => {
      const lista = document.getElementById(`lista-${tipo}s`);
      if (lista.children.length > 1) {
        lista.removeChild(div);
      }
    });

    div.appendChild(sel);
    div.appendChild(btnRemove);

    // popular select com base no tipo
    if (tipo === "area") atualizarSelects([sel], areasDisponiveis, "Selecione a área");
    if (tipo === "produto") atualizarSelects([sel], produtosDisponiveis, "Selecione o produto");

    return div;
  }

  // === Botão adicionar área ===
  document.querySelector(".add-area").addEventListener("click", () => {
    const lista = document.getElementById("lista-areas");
    if (lista.children.length < areasDisponiveis.length) {
      lista.appendChild(criarCampo("area"));
    } else {
      alert("Você já adicionou todas as áreas disponíveis!");
    }
  });

  // === Botão adicionar produto ===
  document.querySelector(".add-produto").addEventListener("click", () => {
    const lista = document.getElementById("lista-produtos");
    if (lista.children.length < produtosDisponiveis.length) {
      lista.appendChild(criarCampo("produto"));
    } else {
      alert("Você já adicionou todos os produtos disponíveis!");
    }
  });
});
