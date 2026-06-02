document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-herbicida");

  // === Carregar ÁREAS ===
  function carregarAreas() {
    fetch("../funcoes/buscar_areas.php")
      .then(r => r.json())
      .then(data => {

        document.querySelectorAll(".area-select").forEach(sel => {

          const valorAtual = sel.value; // guarda seleção atual

          sel.innerHTML = '<option value="">Selecione a área</option>';

          data.forEach(item => {

            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = `${item.nome} (${item.tipo})`;

            // restaura seleção anterior
            if (item.id == valorAtual) {
              opt.selected = true;
            }

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
      const original = lista.querySelector(".area-select");

      if (!original) return;

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

        const total = document.querySelectorAll("#lista-areas .form-box-area").length;

        if (total > 1) {
          wrapper.remove();
        } else {
          alert("É necessário manter pelo menos uma área.");
        }

      };

      wrapper.appendChild(novo);
      wrapper.appendChild(btnRemover);

      lista.appendChild(wrapper);

      /* carregar áreas no novo select */

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
    fetch("/funcoes/buscar_herbicidas.php", { credentials: "same-origin" })
      .then((r) => r.json())
      .then((data) => {
        const sel = document.getElementById("herbicida");
        if (!sel) return;
        sel.innerHTML = '<option value="">Selecione o herbicida</option>';
        if (Array.isArray(data)) {
          data.forEach((item) => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = item.nome;
            sel.appendChild(opt);
          });
        }
        if (typeof DefensivoOutro !== "undefined") {
          DefensivoOutro.afterCatalogLoaded("herbicida");
        }
      })
      .catch((err) => {
        console.error("Erro ao carregar herbicidas:", err);
        if (typeof DefensivoOutro !== "undefined") {
          DefensivoOutro.afterCatalogLoaded("herbicida");
        }
      });
  }

  carregarHerbicidas();

  // === Submit do formulário principal ===
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const beforeSubmit = (fd) => {
        const sel = document.getElementById("herbicida");
        const outro = document.getElementById("herbicida_outro");
        if (sel && outro && sel.value === "outro") {
          fd.set("herbicida", outro.value.trim());
        }
      };
      if (typeof CadernoSalvar !== "undefined") {
        CadernoSalvar.submitForm(form, "salvar_herbicida.php", { beforeSubmit });
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
