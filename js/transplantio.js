document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-transplantio");

  // === Carregar áreas nas listas ===
  function carregarAreas() {
    fetch("../funcoes/buscar_areas.php")
      .then(r => r.json())
      .then(data => {
        document.querySelectorAll(".area-origem-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione a área de origem</option>';
          data.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = `${item.nome} (${item.tipo})`;
            sel.appendChild(opt);
          });
        });

        document.querySelectorAll(".area-destino-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione a área de destino</option>';
          data.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = `${item.nome} (${item.tipo})`;
            sel.appendChild(opt);
          });
        });
      });
  }

  // === Botão adicionar área de origem ===
  document.querySelector(".add-origem").addEventListener("click", () => {
    const lista = document.getElementById("lista-origens");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);

    novo.value = "";
    novo.removeAttribute("id");
    novo.name = "area_origem[]";
    novo.classList.add("area-origem-select");

    const wrapper = document.createElement("div");
    wrapper.className = "form-box form-box-area";
    wrapper.appendChild(novo);

    lista.appendChild(wrapper);
    carregarAreas();
  });

  // === Botão adicionar área de destino ===
  document.querySelector(".add-destino").addEventListener("click", () => {
    const lista = document.getElementById("lista-destinos");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);

    novo.value = "";
    novo.removeAttribute("id");
    novo.name = "area_destino[]";
    novo.classList.add("area-destino-select");

    const wrapper = document.createElement("div");
    wrapper.className = "form-box form-box-area";
    wrapper.appendChild(novo);

    lista.appendChild(wrapper);
    carregarAreas();
  });

  // === Carregar produtos ===
  fetch("../funcoes/buscar_produtos.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("produto");
      sel.innerHTML = '<option value="">Selecione o produto</option>';
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome;
        sel.appendChild(opt);
      });
    });

  // === Carrega áreas no início ===
  carregarAreas();

  // === Submit ===
  form.addEventListener("submit", e => {
    e.preventDefault();
    const dados = new FormData(form);

    fetch("../funcoes/salvar_transplantio.php", {
      method: "POST",
      body: dados
    })
      .then(r => r.json())
      .then(res => {
        if (res.ok) {
          showPopup("success", res.msg || "Transplantio salvo com sucesso!");
          form.reset();
          carregarAreas();
        } else {
          showPopup("failed", res.err || "Erro ao salvar o transplantio.");
        }
      })
      .catch(err => {
        showPopup("failed", "Falha na comunicação: " + err);
      });
  });
});
