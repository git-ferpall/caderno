document.addEventListener("DOMContentLoaded", () => {
  const overlay = document.getElementById("popup-overlay");
  const popupDetalhe = document.getElementById("popup-detalhe-manejo");
  const btnConcluir = document.getElementById("btn-marcar-concluido");

  // === 1️⃣ Função global para associar clique às linhas da tabela ===
  window.inicializarPopupLinhas = function () {
    const linhas = document.querySelectorAll(".apontamento-fazer tbody tr");

    linhas.forEach((tr) => {
      tr.addEventListener("click", () => {
        const id = tr.dataset.id;
        abrirPopupManejo(id);
      });
    });
  };

  // === 2️⃣ Abre popup com detalhes do manejo ===
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

        document.getElementById("manejo-areas").innerHTML =
          a.areas.map((n) => `• ${n}`).join("<br>");
        document.getElementById("manejo-produtos").innerHTML =
          a.produtos.map((n) => `• ${n}`).join("<br>");

        const extras = Object.entries(a.detalhes)
          .map(([k, v]) => `<div><b>${k}:</b> ${v}</div>`)
          .join("");
        document.getElementById("manejo-detalhes-extra").innerHTML =
          extras || "<em>Sem detalhes adicionais.</em>";

        // Mostra popup
        overlay.classList.remove("d-none");
        popupDetalhe.classList.remove("d-none");

        // Guarda o ID do manejo no botão de concluir
        btnConcluir.dataset.id = a.id;
      })
      .catch((err) => alert("Erro: " + err));
  }

  // === 3️⃣ Clique em "Marcar como concluído" ===
  if (btnConcluir) {
    btnConcluir.addEventListener("click", () => {
      const id = btnConcluir.dataset.id;
      if (!id) {
        alert("ID inválido para marcar como concluído.");
        return;
      }

      fetch("../funcoes/marcar_concluido.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + encodeURIComponent(id),
      })
        .then((r) => r.json())
        .then((res) => {
          if (res.ok) {
            // Fecha o popup atual
            closePopup();

            // Usa o popup verde padrão (popup-ativar)
            const popupSucesso = document.getElementById("popup-ativar");
            overlay.classList.remove("d-none");
            popupSucesso.classList.remove("d-none");

            // Atualiza o texto para o contexto do manejo
            popupSucesso.querySelector(".popup-title").textContent =
              "Manejo concluído com sucesso!";

            // Botão OK fecha e recarrega
            const btnOk = popupSucesso.querySelector("#btn-ok");
            btnOk.onclick = function () {
              closePopup();
              location.reload();
            };
          } else {
            // Exibe popup de erro
            const popupFail = document.getElementById("popup-failed");
            overlay.classList.remove("d-none");
            popupFail.classList.remove("d-none");
            popupFail.querySelector(".popup-text").textContent =
              res.msg || "Erro ao marcar manejo como concluído.";
          }
        })
        .catch((err) => {
          const popupFail = document.getElementById("popup-failed");
          overlay.classList.remove("d-none");
          popupFail.classList.remove("d-none");
          popupFail.querySelector(".popup-text").textContent =
            "Falha na requisição: " + err;
        });
    });
  }

  // === 4️⃣ Função utilitária de formatação de data ===
  function formatarData(str) {
    const d = new Date(str);
    if (isNaN(d)) return str;
    return d.toLocaleDateString("pt-BR", { timeZone: "UTC" });
  }
});
