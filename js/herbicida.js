document.addEventListener("DOMContentLoaded", () => {
  // === Carregar ÁREAS ===
  fetch("../funcoes/buscar_areas.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("area");
      sel.innerHTML = '<option value="">Selecione a área</option>';
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = `${item.nome} (${item.tipo})`;
        sel.appendChild(opt);
      });
    })
    .catch(err => console.error("Erro ao carregar áreas:", err));

  // === Submit do formulário ===
  const form = document.getElementById("form-herbicida");
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
        } else {
          showPopup("failed", res.err || "Erro ao salvar herbicida.");
        }
      })
      .catch(err => {
        showPopup("failed", "Falha na comunicação: " + err);
      });
  });
});

// === Função popup padrão ===
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
document.addEventListener("DOMContentLoaded", () => {
  const formSolicitar = document.getElementById("form-solicitar-herbicida");

  // === Função para abrir popup ===
  window.abrirPopup = function (id) {
    const overlay = document.getElementById("popup-overlay");
    const popup = document.getElementById(id);

    if (overlay && popup) {
      overlay.classList.remove("d-none");
      popup.classList.remove("d-none");
    }
  };

  // === Envio do formulário de solicitação ===
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
            showPopup("success", res.msg);
            closePopup(); // fecha o popup de solicitação
          } else {
            showPopup("failed", res.msg);
          }
        })
        .catch(() => {
          showPopup("failed", "Erro ao enviar a solicitação.");
        });
    });
  }
});
