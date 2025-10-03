document.addEventListener("DOMContentLoaded", () => {
  // Carregar áreas
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

  // Botão adicionar área
  const btnAddArea = document.querySelector(".add-area");
  if (btnAddArea) {
    btnAddArea.addEventListener("click", () => {
      const lista = document.getElementById("lista-areas");
      const original = lista.querySelector("select");
      if (!original) return;

      const novo = original.cloneNode(true);
      novo.value = "";
      novo.name = "area[]"; // importante: array
      novo.classList.add("area-select");

      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-area";
      wrapper.appendChild(novo);

      lista.appendChild(wrapper);
      carregarAreas();
    });
  }

  // Submit
  const form = document.getElementById("form-fertilizante");
  form.addEventListener("submit", e => {
    e.preventDefault();
    const dados = new FormData(form);

    fetch("../funcoes/salvar_fertilizante.php", {
      method: "POST",
      body: dados
    })
      .then(r => r.json())
      .then(res => {
        if (res.ok) {
          showPopup("success", res.msg || "Fertilizante salvo com sucesso!");
          form.reset();
          carregarAreas();
        } else {
          showPopup("failed", res.err || "Erro ao salvar o fertilizante.");
        }
      })
      .catch(err => {
        showPopup("failed", "Falha na comunicação: " + err);
      });
  });
});
