document.addEventListener("DOMContentLoaded", () => {
  // Carregar áreas
  fetch("../apontamentos/funcoes/buscar_areas.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("area");
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome_razao;
        sel.appendChild(opt);
      });
    });

  // Carregar produtos
  fetch("../apontamentos/funcoes/buscar_produtos.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("produto");
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome;
        sel.appendChild(opt);
      });
    });

  // Evita duplicar seleção
  ["area","produto"].forEach(id => {
    document.getElementById(id).addEventListener("change", e => {
      const val = e.target.value;
      document.querySelectorAll(`#${id} option`).forEach(opt => {
        opt.disabled = false; // reseta
      });
      if (val) {
        document.querySelectorAll(`#${id} option[value='${val}']`).forEach(opt => {
          if (opt.parentElement !== e.target) opt.disabled = true;
        });
      }
    });
  });

  // Botões de adicionar
  document.querySelector(".add-area").addEventListener("click", () => {
    alert("Abrir modal/cadastro rápido de Área");
  });

  document.querySelector(".add-produto").addEventListener("click", () => {
    alert("Abrir modal/cadastro rápido de Produto");
  });
});
