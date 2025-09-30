document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-clima");

  form.addEventListener("submit", e => {
    e.preventDefault();

    const dados = new FormData(form);

    fetch("../funcoes/salvar_clima.php", {
      method: "POST",
      body: dados
    })
      .then(r => r.json())
      .then(res => {
        if (res.ok) {
          showPopup("success", res.msg || "Registro climático salvo com sucesso!");
          form.reset();
        } else {
          showPopup("failed", res.err || "Erro ao salvar registro climático.");
        }
      })
      .catch(err => {
        showPopup("failed", "Falha na comunicação: " + err);
      });
  });
});

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
