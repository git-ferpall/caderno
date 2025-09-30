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

  // === Botão adicionar área (futuro modal rápido) ===
  document.querySelector(".add-area").addEventListener("click", () => {
    console.log("Botão Adicionar Área clicado");
  });

  // === Submit do formulário ===
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
        } else {
          showPopup("failed", res.err || "Erro ao salvar o fertilizante.");
        }
      })
      .catch(err => {
        showPopup("failed", "Falha na comunicação: " + err);
      });
  });
});

// === Popups padrão do sistema ===
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
