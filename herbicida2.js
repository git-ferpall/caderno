document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-herbicida");

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
      novo.name = "area[]"; // importante: array
      novo.classList.add("area-select");

      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-area";
      wrapper.appendChild(novo);

      lista.appendChild(wrapper);

      // recarregar opções para o novo select
      fetch("../funcoes/buscar_areas.php")
        .then(r => r.json())
        .then(data => {
          novo.innerHTML = '<option value="">Selecione a área</option>';
          data.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = `${item.nome} (${item.tipo})`;
            novo.appendChild(opt);
          });
        });
    });
  }

  // === Carregar HERBICIDAS ===
  function carregarHerbicidas() {
    fetch("../funcoes/buscar_herbicidas.php")
      .then(r => r.json())
      .then(data => {
        const sel = document.getElementById("herbicida");
        if (!sel) return;
        sel.innerHTML = '<option value="">Selecione o herbicida</option>';
        data.forEach(item => {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = item.nome;
          sel.appendChild(opt);
        });

        // 🔹 Adiciona a opção "Outro" no final da lista
        const outro = document.createElement("option");
        outro.value = "outro";
        outro.textContent = "Outro (digitar manualmente)";
        sel.appendChild(outro);
      })
      .catch(err => console.error("Erro ao carregar herbicidas:", err));
  }

  carregarHerbicidas();

  // === Submit do formulário principal ===
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const dados = new FormData(form);

      fetch("../funcoes/salvar_herbicida.php", {
        method: "POST",
        body: dados
      })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            showPopup("success", res.msg || "Herbicida salvo com sucesso!");
            form.reset();
            carregarAreas();
            carregarHerbicidas();
          } else {
            showPopup("failed", res.err || "Erro ao salvar herbicida.");
          }
        })
        .catch(err => {
          showPopup("failed", "Falha na comunicação: " + err);
        });
    });
  }

  // === Mostrar/ocultar campo "Outro" ===
  const herbicidaSelect = document.getElementById("herbicida");
  const herbicidaOutro = document.getElementById("herbicida_outro");

  if (herbicidaSelect && herbicidaOutro) {
    herbicidaSelect.addEventListener("change", () => {
      if (herbicidaSelect.value === "outro") {
        herbicidaOutro.style.display = "block";
        herbicidaOutro.required = true;
        herbicidaOutro.focus();
      } else {
        herbicidaOutro.style.display = "none";
        herbicidaOutro.required = false;
        herbicidaOutro.value = "";
      }
    });
  }

  // === Envio do formulário de solicitação ===
  const formSolicitar = document.getElementById("form-solicitar-herbicida");
  if (formSolicitar) {
    formSolicitar.addEventListener("submit", e => {
      e.preventDefault();
      const dados = new FormData(formSolicitar);

      fetch("../funcoes/solicitar_herbicida.php", {
        method: "POST",
        body: dados
      })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            showPopup("success", "✅ Solicitação enviada com sucesso! Aguarde retorno por e-mail.");

            // fecha apenas o popup de solicitação
            const popupSolicitacao = document.getElementById("popup-solicitar-herbicida");
            if (popupSolicitacao) {
              popupSolicitacao.classList.add("d-none");
            }

            formSolicitar.reset();
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

// === Função padrão de popup ===
function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");

  document.querySelectorAll(".popup-box").forEach(p => p.classList.add("d-none"));
  overlay.classList.remove("d-none");

  if (tipo === "success") {
    popupSuccess.classList.remove("d-none");
    popupSuccess.querySelector(".popup-title").textContent = mensagem;
  } else {
    popupFailed.classList.remove("d-none");
    popupFailed.querySelector(".popup-text").textContent = mensagem;
  }

  setTimeout(() => {
    overlay.classList.add("d-none");
    popupSuccess?.classList.add("d-none");
    popupFailed?.classList.add("d-none");
  }, 4000);
}

// === Função para abrir popup de solicitação de herbicida ===
function abrirPopup(id) {
  const overlay = document.getElementById("popup-overlay");
  const popup = document.getElementById(id);

  if (overlay && popup) {
    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");
  }
}
