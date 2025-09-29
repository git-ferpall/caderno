document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-plantio");
  const selArea = document.getElementById("area");
  const selProduto = document.getElementById("produto");

  // Carregar áreas
  fetch("../funcoes/buscar_areas.php")
    .then(r => r.json())
    .then(data => {
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome_razao;
        selArea.appendChild(opt);
      });
    });

  // Carregar produtos
  fetch("../funcoes/buscar_produtos.php")
    .then(r => r.json())
    .then(data => {
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome;
        selProduto.appendChild(opt);
      });
    });

  // Evitar seleção duplicada
  [selArea, selProduto].forEach(sel => {
    sel.addEventListener("change", e => {
      const val = e.target.value;
      Array.from(sel.options).forEach(opt => opt.disabled = false);
      if (val) {
        sel.querySelectorAll(`option[value='${val}']`).forEach(opt => opt.disabled = true);
      }
    });
  });

  // Enviar form
  form.addEventListener("submit", e => {
    e.preventDefault();
    const fd = new FormData(form);
    fetch("../funcoes/salvar_plantio.php", { method: "POST", body: fd })
      .then(r => r.json())
      .then(resp => {
        if (resp.status === "ok") {
          alert("Plantio salvo com sucesso!");
          form.reset();
        } else {
          alert("Erro: " + resp.msg);
        }
      })
      .catch(err => alert("Erro ao salvar: " + err));
  });

  // Botões de adicionar (apenas alerta por enquanto)
  document.querySelector(".add-area").addEventListener("click", () => {
    alert("Abrir cadastro rápido de Área");
  });
  document.querySelector(".add-produto").addEventListener("click", () => {
    alert("Abrir cadastro rápido de Produto");
  });
});
