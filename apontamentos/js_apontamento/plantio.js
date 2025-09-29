document.addEventListener("DOMContentLoaded", () => {
  // Carregar áreas
  fetch("funcoes/buscar_areas.php")
    .then(r => r.json())
    .then(data => {
      console.log("Áreas recebidas:", data); // debug
      const sel = document.getElementById("area");
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome_razao;
        sel.appendChild(opt);
      });
    })
    .catch(err => console.error("Erro ao carregar áreas:", err));

  // Carregar produtos
  fetch("funcoes/buscar_produtos.php")
    .then(r => r.json())
    .then(data => {
      console.log("Produtos recebidos:", data); // debug
      const sel = document.getElementById("produto");
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome;
        sel.appendChild(opt);
      });
    })
    .catch(err => console.error("Erro ao carregar produtos:", err));

  // Evita duplicar seleção
  ["area","produto"].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return; // segurança
    el.addEventListener("change", e => {
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
  const btnArea = document.querySelector(".add-area");
  if (btnArea) {
    btnArea.addEventListener("click", () => {
      alert("Abrir modal/cadastro rápido de Área");
    });
  }

  const btnProduto = document.querySelector(".add-produto");
  if (btnProduto) {
    btnProduto.addEventListener("click", () => {
      alert("Abrir modal/cadastro rápido de Produto");
    });
  }
});
