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
  const statusSelect = document.getElementById("status");
  const avisoStatus = document.getElementById("aviso-status-semeadura");

  function atualizarAvisoStatus() {
    if (!avisoStatus || !statusSelect) return;
    if (statusSelect.value === "pendente") {
      avisoStatus.textContent = "Aparecerá em «Manejo a fazer» até você concluir.";
      avisoStatus.style.color = "#e65100";
    } else if (statusSelect.value === "concluido") {
      avisoStatus.textContent = "Será registrada direto em «Manejo concluído».";
      avisoStatus.style.color = "#2e7d32";
    } else {
      avisoStatus.textContent = "";
    }
  }

  statusSelect?.addEventListener("change", atualizarAvisoStatus);
  atualizarAvisoStatus();

  function salvarSemeadura(e) {
    if (e) e.preventDefault();
    if (typeof CadernoSalvar === "undefined") {
      alert("Erro ao carregar o módulo de salvamento. Recarregue a página.");
      return;
    }
    CadernoSalvar.submitForm(form, "salvar_semeadura.php");
  }

  if (form) {
    form.addEventListener("submit", salvarSemeadura);
  }
});
