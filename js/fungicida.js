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

  function carregarFungicidas() {
  fetch("../funcoes/buscar_fungicidas.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("fungicida");
      const outroInput = document.getElementById("fungicida_outro");
      if (!sel) return;

      sel.innerHTML = '<option value="">Selecione o fungicida</option>';

      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome;
        sel.appendChild(opt);
      });

      // adiciona "Outro"
      const outro = document.createElement("option");
      outro.value = "outro";
      outro.textContent = "Outro (digitar manualmente)";
      sel.appendChild(outro);
    })
    .catch(err => console.error("Erro ao carregar fungicidas:", err));
}

// 👇 REGISTRA O CHANGE FORA DO FETCH
document.addEventListener("change", function(e) {
  if (e.target && e.target.id === "fungicida") {
    const sel = e.target;
    const outroInput = document.getElementById("fungicida_outro");

    if (sel.value === "outro") {
      outroInput.style.display = "block";
      outroInput.required = true;
      outroInput.focus();
    } else {
      outroInput.style.display = "none";
      outroInput.required = false;
      outroInput.value = "";
    }
  }
});

carregarFungicidas();

  // === Submit do formulário principal ===
    const form = document.getElementById("form-fungicida");
    if (form) {
      form.addEventListener("submit", e => {
        e.preventDefault();
        const dados = new FormData(form);

        // ✅ Se escolheu "outro", envia o texto digitado
        const fungicidaSelect = document.getElementById("fungicida");
        const fungicidaOutro = document.getElementById("fungicida_outro");

        if (fungicidaSelect && fungicidaOutro && fungicidaSelect.value === "outro") {
          dados.set("fungicida", fungicidaOutro.value.trim());
        }

        fetch("../funcoes/salvar_fungicida.php", {
          method: "POST",
          body: dados
        })
          .then(r => r.json())
          .then(res => {
            if (res.ok) {
              showPopup("success", res.msg || "Fungicida salvo com sucesso!");

              setTimeout(() => {
                window.location.href = "/apontamento.php";
              }, 1200);

            } else {
              showPopup("failed", res.err || "Erro ao salvar o fungicida.");
            }
          })
          .catch(err => {
            showPopup("failed", "Falha na comunicação: " + err);
          });
      });
    }


  // === Envio do formulário de solicitação de FUNGICIDA ===
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
            showPopup("success", res.msg || "✅ Solicitação enviada com sucesso! Aguarde retorno por e-mail.");
            formSolicitarFungicida.reset();

            // fecha popup somente depois de exibir a mensagem
            setTimeout(() => {
              document.getElementById("popup-solicitar-fungicida").classList.add("d-none");
              document.getElementById("popup-overlay").classList.add("d-none");
            }, 3000);
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

// Função de popup
function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");

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

// ⚠️ DEIXE FORA DO DOMContentLoaded
function abrirPopup(id) {
  const overlay = document.getElementById("popup-overlay");
  const popup = document.getElementById(id);
  if (overlay && popup) {
    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");
  }
}

