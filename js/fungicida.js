document.addEventListener("DOMContentLoaded", () => {

  // === Carregar ÁREAS ===
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

      // botão remover
      const btnRemover = document.createElement("button");
      btnRemover.type = "button";
      btnRemover.textContent = "❌";
      btnRemover.className = "remove-btn";
      btnRemover.style.marginLeft = "8px";
      btnRemover.addEventListener("click", () => {
        wrapper.remove();
      });

      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-area";
      wrapper.appendChild(novo);
      wrapper.appendChild(btnRemover);

      lista.appendChild(wrapper);
      carregarAreas();
    });
  }

  // === Carregar FUNGICIDAS ===
  fetch("../funcoes/buscar_fungicidas.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("fungicida");
      if (!sel) return;
      sel.innerHTML = '<option value="">Selecione o fungicida</option>';
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome;
        sel.appendChild(opt);
      });
    })
    .catch(err => console.error("Erro ao carregar fungicidas:", err));

  // === Submit do formulário principal ===
  const form = document.getElementById("form-fungicida");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const dados = new FormData(form);

      fetch("../funcoes/salvar_fungicida.php", {
        method: "POST",
        body: dados
      })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            showPopup("success", res.msg || "Fungicida salvo com sucesso!");
            form.reset();
            carregarAreas();
          } else {
            showPopup("failed", res.err || "Erro ao salvar o fungicida.");
          }
        })
        .catch(err => {
          showPopup("failed", "Falha na comunicação: " + err);
        });
    });
  }

  // === Submit do formulário de solicitação ===
  const formSolicitarFungicida = document.getElementById("form-solicitar-fungicida");
  if (formSolicitarFungicida) {
    formSolicitarFungicida.addEventListener("submit", e => {
      e.preventDefault();
      const dados = new FormData(formSolicitarFungicida);

      fetch("../funcoes/solicitar_fungicida.php", {
        method: "POST",
        body: dados
      })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            showPopup("success", res.msg || "Solicitação enviada com sucesso!");
            formSolicitarFungicida.reset();
            // fecha popup
            document.getElementById("popup-solicitar-fungicida").classList.add("d-none");
            document.getElementById("popup-overlay").classList.add("d-none");
          } else {
            showPopup("failed", res.msg || "Erro ao salvar solicitação.");
          }
        })
        .catch(err => {
          showPopup("failed", "Falha na comunicação: " + err);
        });
    });
  }
});

// === Popup padrão ===
function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");

  if (!overlay) return;

  overlay.classList.remove("d-none");

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
    overlay.classList.add("d-none");
    popupSuccess?.classList.add("d-none");
    popupFailed?.classList.add("d-none");
  }, 4000);
}

// === Função abrir popup (solicitação) ===
function abrirPopup(id) {
  const overlay = document.getElementById("popup-overlay");
  const popup = document.getElementById(id);
  if (overlay && popup) {
    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");
  }
}
