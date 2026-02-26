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

      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-area";
      wrapper.appendChild(novo);

      lista.appendChild(wrapper);
      carregarAreas();
    });
  }

  
  // === Carregar inseticidas ===
  function carregarInseticidas() {
    fetch("../funcoes/buscar_inseticidas.php")
      .then(r => r.json())
      .then(data => {
        const sel = document.getElementById("inseticida");
        const outroInput = document.getElementById("inseticida_outro");
        if (!sel) return;

        // limpa e recria as opções
        sel.innerHTML = '<option value="">Selecione o inseticida</option>';
        data.forEach(item => {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = item.nome;
          sel.appendChild(opt);
        });

        // adiciona a opção "Outro (digitar manualmente)"
        const outro = document.createElement("option");
        outro.value = "outro";
        outro.textContent = "Outro (digitar manualmente)";
        sel.appendChild(outro);

        // mostra/oculta o campo manual
        sel.addEventListener("change", () => {
          if (sel.value === "outro") {
            outroInput.style.display = "block";
            outroInput.required = true;
            outroInput.focus();
          } else {
            outroInput.style.display = "none";
            outroInput.required = false;
            outroInput.value = "";
          }
        });
      })
      .catch(err => console.error("Erro ao carregar inseticidas:", err));
  }

carregarInseticidas();


  // === Submit do formulário principal ===
  const form = document.getElementById("form-inseticida");
  if (form) {
    form.addEventListener("submit", e => {
      e.preventDefault();
      const dados = new FormData(form);

      // ✅ Se o usuário escolheu "outro", envia o texto digitado no lugar
      const inseticidaSelect = document.getElementById("inseticida");
      const inseticidaOutro = document.getElementById("inseticida_outro");
      if (inseticidaSelect && inseticidaOutro && inseticidaSelect.value === "outro") {
        dados.set("inseticida", inseticidaOutro.value.trim());
      }

      fetch("../funcoes/salvar_inseticida.php", {
        method: "POST",
        body: dados
      })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            showPopup("success", res.msg || "Dados salvos com sucesso!");

            setTimeout(() => {
              window.location.href = "apontamento.php";
            }, 1200);
          } else {
            showPopup("failed", res.err || "Erro ao salvar inseticida.");
          }
        })
        .catch(err => {
          showPopup("failed", "Falha na comunicação: " + err);
        });
    });
  }


  // === Envio do formulário de solicitação de inseticida ===
  const formSolicitarInseticida = document.getElementById("form-solicitar-inseticida");
  if (formSolicitarInseticida) {
    formSolicitarInseticida.addEventListener("submit", e => {
      e.preventDefault();
      const dados = new FormData(formSolicitarInseticida);

      fetch("../funcoes/solicitar_inseticida.php", {
        method: "POST",
        body: dados
      })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            // Mostra popup de sucesso
            showPopup("success", res.msg || "✅ Solicitação enviada com sucesso! Aguarde retorno por e-mail.");
            formSolicitarInseticida.reset();

            // fecha apenas o popup de solicitação (não fecha overlay, para o sucesso aparecer)
            document.getElementById("popup-solicitar-inseticida").classList.add("d-none");

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

  // esconde todos antes
  document.querySelectorAll(".popup-box").forEach(p => p.classList.add("d-none"));

  overlay.classList.remove("d-none");

  if (tipo === "success") {
    popupSuccess.classList.remove("d-none");
    popupSuccess.querySelector(".popup-title").textContent = mensagem;
  } else {
    popupFailed.classList.remove("d-none");
    popupFailed.querySelector(".popup-text").textContent = mensagem;
  }

  // fecha automaticamente depois de 4s
  setTimeout(() => {
    overlay.classList.add("d-none");
    popupSuccess?.classList.add("d-none");
    popupFailed?.classList.add("d-none");
  }, 4000);
}

// === Função para abrir popups (como o de solicitar inseticida) ===
function abrirPopup(id) {
  const overlay = document.getElementById("popup-overlay");
  const popup = document.getElementById(id);
  if (overlay && popup) {
    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");
  }
}
