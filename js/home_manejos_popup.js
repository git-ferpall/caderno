document.addEventListener("DOMContentLoaded", () => {
  const overlay = document.getElementById("popup-overlay");
  const popupDetalhe = document.getElementById("popup-detalhe-manejo");
  const btnConcluir = document.getElementById("btn-marcar-concluido");

  // Tornamos a função acessível globalmente
  window.inicializarPopupLinhas = function () {
    const linhas = document.querySelectorAll(".apontamento-fazer tbody tr");

    linhas.forEach((tr) => {
      tr.addEventListener("click", () => {
        const id = tr.dataset.id;
        abrirPopupManejo(id);
      });
    });
  };

  // Busca os detalhes via PHP e preenche popup
  function abrirPopupManejo(id) {
    fetch("../funcoes/buscar_apontamento.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then((r) => r.json())
      .then((data) => {
        if (!data.ok) {
          alert(data.msg || "Erro ao buscar detalhes do manejo.");
          return;
        }

        const a = data.apontamento;

        document.getElementById("manejo-data").value = formatarData(a.data);
        document.getElementById("manejo-tipo").value = a.tipo;
        document.getElementById("manejo-status").value = a.status.toUpperCase();

        // listas
        document.getElementById("manejo-areas").innerHTML =
          a.areas.map((n) => `• ${n}`).join("<br>");
        document.getElementById("manejo-produtos").innerHTML =
          a.produtos.map((n) => `• ${n}`).join("<br>");

        // Detalhes extras
        const extras = Object.entries(a.detalhes)
          .map(([k, v]) => `<div><b>${k}:</b> ${v}</div>`)
          .join("");
        document.getElementById("manejo-detalhes-extra").innerHTML =
          extras || "<em>Sem detalhes adicionais.</em>";

        overlay.classList.remove("d-none");
        popupDetalhe.classList.remove("d-none");

        btnConcluir.onclick = () => marcarComoConcluido(a.id);
      })
      .catch((err) => alert("Erro: " + err));
  }

  function marcarComoConcluido(id) {
    if (!id) return;
    fetch("../funcoes/marcar_concluido.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.ok) {
          closePopup();
          const popupSuccess = document.getElementById("popup-success");
          overlay.classList.remove("d-none");
          popupSuccess.classList.remove("d-none");
          popupSuccess.querySelector(".popup-title").textContent =
            "Manejo marcado como concluído!";
          document
            .getElementById("btn-ok")
            .addEventListener("click", () => location.reload(), { once: true });
        } else {
          alert(data.msg || "Erro ao marcar como concluído.");
        }
      })
      .catch((err) => alert("Erro: " + err));
  }

  function formatarData(str) {
    const d = new Date(str);
    if (isNaN(d)) return str;
    return d.toLocaleDateString("pt-BR", { timeZone: "UTC" });
  }
});
