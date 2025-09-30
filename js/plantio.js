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

  // === Botão adicionar ÁREA ===
  // Botões de adicionar (sem alert)
  document.querySelector(".add-area").addEventListener("click", () => {
    // Aqui futuramente podemos abrir o modal de cadastro rápido de área
    console.log("Botão Adicionar Área clicado");
  });

  document.querySelector(".add-produto").addEventListener("click", () => {
    // Aqui futuramente podemos abrir o modal de cadastro rápido de produto
    console.log("Botão Adicionar Produto clicado");
  });

});
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-plantio");

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const dados = new FormData(form);

    fetch("../funcoes/salvar_plantio.php", {
      method: "POST",
      body: dados
    })
    .then(r => r.json())
    .then(res => {
      if (res.ok) {
        alert("Plantio salvo com sucesso!");
        form.reset();
      } else {
        alert("Erro: " + res.err);
      }
    })
    .catch(err => {
      console.error("Erro no envio:", err);
      alert("Falha ao salvar no servidor.");
    });
  });
});

