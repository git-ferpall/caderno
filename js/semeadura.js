document.addEventListener("DOMContentLoaded", () => {

  function carregarAreas(select = null) {
    fetch("/funcoes/buscar_areas.php", { credentials: "same-origin" })
      .then(r => r.json())
      .then(data => {
        if (!Array.isArray(data)) return;
        const selects = select ? [select] : document.querySelectorAll(".area-select");
        selects.forEach(sel => {
          const valorAtual = sel.value;
          sel.innerHTML = '<option value="">Selecione a área</option>';
          data.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = `${item.nome} (${item.tipo})`;
            if (item.id == valorAtual) opt.selected = true;
            sel.appendChild(opt);
          });
        });
      })
      .catch(err => console.error("Erro ao carregar áreas:", err));
  }

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
            if (item.id == valorAtual) opt.selected = true;
            sel.appendChild(opt);
          });
        });
      })
      .catch(err => console.error("Erro ao carregar produtos:", err));
  }

  document.querySelector(".add-area")?.addEventListener("click", () => {
    const lista = document.getElementById("lista-areas");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);
    novo.value = "";
    novo.name = "area[]";
    novo.classList.add("area-select");

    const wrapper = document.createElement("div");
    wrapper.className = "form-box form-box-area linha";

    const btnRemover = document.createElement("button");
    btnRemover.type = "button";
    btnRemover.className = "remove-btn";
    btnRemover.innerHTML = "−";
    btnRemover.onclick = () => {
      if (document.querySelectorAll(".form-box-area").length > 1) wrapper.remove();
      else alert("É necessário manter pelo menos uma área.");
    };

    wrapper.appendChild(novo);
    wrapper.appendChild(btnRemover);
    lista.appendChild(wrapper);
    carregarAreas(novo);
  });

  document.querySelector(".add-produto")?.addEventListener("click", () => {
    const lista = document.getElementById("lista-produtos");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);
    novo.value = "";
    novo.name = "produto[]";
    novo.classList.add("produto-select");

    const wrapper = document.createElement("div");
    wrapper.className = "form-box form-box-produto linha";

    const btnRemover = document.createElement("button");
    btnRemover.type = "button";
    btnRemover.className = "remove-btn";
    btnRemover.innerHTML = "−";
    btnRemover.onclick = () => {
      if (document.querySelectorAll(".form-box-produto").length > 1) wrapper.remove();
      else alert("É necessário manter pelo menos um produto.");
    };

    wrapper.appendChild(novo);
    wrapper.appendChild(btnRemover);
    lista.appendChild(wrapper);
    carregarProdutos(novo);
  });

  carregarAreas();
  carregarProdutos();

  const dataInput = document.getElementById("data");
  if (dataInput && !dataInput.value) {
    dataInput.value = new Date().toISOString().slice(0, 10);
  }

  const form = document.getElementById("form-semeadura");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      if (typeof CadernoSalvar !== "undefined") {
        CadernoSalvar.submitForm(form, "salvar_semeadura.php");
      } else {
        alert("Erro ao carregar o módulo de salvamento. Recarregue a página.");
      }
    });
  }
});
