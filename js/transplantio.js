document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-transplantio");

  // === Função para carregar áreas nos selects ===
  function carregarAreas() {
    fetch("../funcoes/buscar_areas.php")
      .then(r => r.json())
      .then(data => {
        // Origem
        document.querySelectorAll(".area-origem-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione a área de origem</option>';
          data.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = `${item.nome} (${item.tipo})`;
            sel.appendChild(opt);
          });
        });

        // Destino
        document.querySelectorAll(".area-destino-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione a área de destino</option>';
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

  // === Função para carregar produtos ===
  function carregarProdutos() {
    fetch("../funcoes/buscar_produtos.php")
      .then(r => r.json())
      .then(data => {
        const sel = document.getElementById("produto");
        if (!sel) return;
        sel.innerHTML = '<option value="">Selecione o produto</option>';
        data.forEach(item => {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = item.nome;
          sel.appendChild(opt);
        });
      })
      .catch(err => console.error("Erro ao carregar produtos:", err));
  }

  // === Botão adicionar área de origem ===
  const btnAddOrigem = document.querySelector(".add-origem");
  if (btnAddOrigem) {
    btnAddOrigem.addEventListener("click", () => {
      const lista = document.getElementById("lista-origens");
      const original = lista.querySelector("select");
      if (!original) return;

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
  }

  // === Botão adicionar área de destino ===
  const btnAddDestino = document.querySelector(".add-destino");
  if (btnAddDestino) {
    btnAddDestino.addEventListener("click", () => {
      const lista = document.getElementById("lista-destinos");
      const original = lista.querySelector("select");
      if (!original) return;

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
  }

  // === Carregar selects iniciais ===
  carregarAreas();
  carregarProdutos();

  // === Submit do formulário ===
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const formData = new FormData(form);

      try {
        const resp = await fetch("../funcoes/salvar_transplantio.php", {
          method: "POST",
          body: formData
        });
        const data = await resp.json();

        if (data.ok) {
          showPopup("success", data.msg || "Transplantio salvo com sucesso!");
          form.reset();
          carregarAreas();
          carregarProdutos();
        } else {
          showPopup("failed", data.err || "Erro ao salvar o transplantio.");
        }
      } catch (err) {
        showPopup("failed", "Falha na comunicação: " + err);
      }
    });
  }
});

// === Função padrão de popup ===
function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");

  document.querySelectorAll(".popup-box").forEach(p => p.classList.add("d-none"));
  if (overlay) overlay.classList.remove("d-none");

  if (tipo === "success") {
    if (popupSuccess) {
      popupSuccess.classList.remove("d-none");
      popupSuccess.querySelector(".popup-title").textContent = mensagem;
    }
  } else {
    if (popupFailed) {
      popupFailed.classList.remove("d-none");
      popupFailed.querySelector(".popup-text").textContent = mensagem;
    }
  }

  setTimeout(() => {
    if (overlay) overlay.classList.add("d-none");
    popupSuccess?.classList.add("d-none");
    popupFailed?.classList.add("d-none");
  }, 4000);
}
