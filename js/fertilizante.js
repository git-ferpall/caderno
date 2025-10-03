document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-fertilizante");

  // === Carregar áreas ===
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

  // === Carregar fertilizantes ===
  function carregarFertilizantes() {
    fetch("../funcoes/buscar_fertilizantes.php")
      .then(r => r.json())
      .then(data => {
        const sel = document.getElementById("fertilizante");
        sel.innerHTML = '<option value="">Selecione o fertilizante</option>';
        data.forEach(item => {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = item.nome;
          sel.appendChild(opt);
        });
      })
      .catch(err => console.error("Erro ao carregar fertilizantes:", err));
  }

  carregarFertilizantes();

  // === Botão adicionar área ===
  const btnAddArea = document.querySelector(".add-area");
  if (btnAddArea) {
    btnAddArea.addEventListener("click", () => {
      const lista = document.getElementById("lista-areas");
      const original = lista.querySelector("select");
      if (!original) return;

      const novo = original.cloneNode(true);
      novo.value = "";
      novo.name = "area[]";
      novo.classList.add("area-select");

      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-area";
      wrapper.appendChild(novo);

      lista.appendChild(wrapper);
      carregarAreas();
    });
  }

  // === Submit do formulário principal ===
  if (form) {
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
            carregarFertilizantes();
          } else {
            showPopup("failed", res.msg || "Erro ao salvar o fertilizante.");
          }
        })
        .catch(err => {
          showPopup("failed", "Falha na comunicação: " + err);
        });
    });
  }

  // === Form de solicitar novo fertilizante ===
  const formSolicitar = document.getElementById("form-solicitar-fertilizante");
  if (formSolicitar) {
    formSolicitar.addEventListener("submit", e => {
      e.preventDefault();
      const dados = new FormData(formSolicitar);

      fetch("../funcoes/solicitar_fertilizante.php", {
        method: "POST",
        body: dados
      })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            showPopup("success", res.msg);
            fecharPopup("popup-solicitar-fertilizante");
            formSolicitar.reset();
            carregarFertilizantes();
          } else {
            showPopup("failed", res.msg || "Erro ao solicitar fertilizante.");
          }
        })
        .catch(err => {
          showPopup("failed", "Falha: " + err);
        });
    });
  }
});

// === Abrir/Fechar popup ===
function abrirPopup(id) {
  const overlay = document.getElementById("popup-overlay");
  const popup = document.getElementById(id);
  overlay?.classList.remove("d-none");
  popup?.classList.remove("d-none");
}

function fecharPopup(id) {
  const overlay = document.getElementById("popup-overlay");
  const popup = document.getElementById(id);
  overlay?.classList.add("d-none");
  popup?.classList.add("d-none");
}

// === Popups padrão ===
function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");

  document.querySelectorAll(".popup-box").forEach(p => p.classList.add("d-none"));
  overlay?.classList.remove("d-none");

  if (tipo === "success") {
    popupSuccess?.classList.remove("d-none");
    popupSuccess?.querySelector(".popup-title").textContent = mensagem;
  } else {
    popupFailed?.classList.remove("d-none");
    popupFailed?.querySelector(".popup-text").textContent = mensagem;
  }

  setTimeout(() => {
    overlay?.classList.add("d-none");
    popupSuccess?.classList.add("d-none");
    popupFailed?.classList.add("d-none");
  }, 4000);
}
