document.addEventListener("DOMContentLoaded", () => {

  const overlay = document.getElementById("popup-overlay");
  const popupDetalhe = document.getElementById("popup-detalhe-manejo");
  const btnConcluir = document.getElementById("btn-marcar-concluido");

  /* =========================================================
     1️⃣ ASSOCIAR CLIQUE NAS LINHAS
  ========================================================= */

  window.inicializarPopupLinhas = function () {
    const linhas = document.querySelectorAll(".apontamento-fazer tbody tr");

    linhas.forEach((tr) => {
      tr.addEventListener("click", () => {
        const id = tr.dataset.id;
        if (id) abrirPopupManejo(id);
      });
    });
  };

  /* =========================================================
     2️⃣ ABRIR POPUP COM DETALHES
  ========================================================= */

  function abrirPopupManejo(id) {

    fetch("../funcoes/buscar_apontamento.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then((r) => r.json())
      .then((data) => {

        if (!data.ok) {
          alert(data.msg || "Erro ao buscar detalhes.");
          return;
        }

        const a = data.apontamento;

        /* ---------- BLOCO COLHEITA ---------- */

        const blocoColheita = document.getElementById("bloco-colheita");

        if (a.tipo === "colheita") {

          blocoColheita?.classList.remove("d-none");

          if (a.quantidade) {
            document.getElementById("colheita-quantidade").value = a.quantidade;
          }

          if (a.unidade) {
            document.getElementById("colheita-unidade").value = a.unidade;
          }

        } else {
          blocoColheita?.classList.add("d-none");
        }

        /* ---------- DADOS PRINCIPAIS ---------- */

        document.getElementById("manejo-data").value = formatarData(a.data);
        document.getElementById("manejo-tipo").value = a.tipo;
        document.getElementById("manejo-status").value = a.status.toUpperCase();

        document.getElementById("manejo-areas").innerHTML =
          a.areas.map((n) => `• ${n}`).join("<br>");

        document.getElementById("manejo-produtos").innerHTML =
          a.produtos.map((n) => `• ${n}`).join("<br>");

        const extras = Object.entries(a.detalhes || {})
          .map(([k, v]) => `<div><b>${k}:</b> ${v}</div>`)
          .join("");

        document.getElementById("manejo-detalhes-extra").innerHTML =
          extras || "<em>Sem detalhes adicionais.</em>";

        /* ---------- EXIBE POPUP ---------- */

        overlay?.classList.remove("d-none");
        popupDetalhe?.classList.remove("d-none");

        btnConcluir.dataset.id = a.id;
      })
      .catch((err) => alert("Erro: " + err));
  }

  /* =========================================================
     3️⃣ MARCAR COMO CONCLUÍDO
  ========================================================= */

  if (btnConcluir) {

    btnConcluir.addEventListener("click", () => {

      const id = btnConcluir.dataset.id;

      if (!id) {
        alert("ID inválido.");
        return;
      }

      let quantidade = null;
      let unidade = null;

      const blocoColheita = document.getElementById("bloco-colheita");

      // 🔸 Se for colheita visível, exigir quantidade
      if (blocoColheita && !blocoColheita.classList.contains("d-none")) {

        quantidade = document.getElementById("colheita-quantidade").value;
        unidade = document.getElementById("colheita-unidade").value;

        if (!quantidade || quantidade <= 0) {
          alert("Informe a quantidade colhida.");
          return;
        }
      }

      /* ---------- ENVIA PARA O BACKEND ---------- */

      fetch("../funcoes/marcar_concluido.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body:
          "id=" + encodeURIComponent(id) +
          "&quantidade=" + encodeURIComponent(quantidade || "") +
          "&unidade=" + encodeURIComponent(unidade || "")
      })
        .then((r) => r.json())
        .then((res) => {

          if (res.ok) {

            popupDetalhe?.classList.add("d-none");

            const popupSucesso = document.getElementById("popup-ativar");

            overlay?.classList.remove("d-none");
            popupSucesso?.classList.remove("d-none");

            popupSucesso.querySelector(".popup-title").textContent =
              "Manejo concluído com sucesso!";

            const btnOk = popupSucesso.querySelector("#btn-ok");

            btnOk.onclick = function () {
              closePopup();
              location.reload();
            };

          } else {

            const popupFail = document.getElementById("popup-failed");

            overlay?.classList.remove("d-none");
            popupFail?.classList.remove("d-none");

            popupFail.querySelector(".popup-text").textContent =
              res.msg || "Erro ao concluir.";
          }
        })
        .catch((err) => {

          const popupFail = document.getElementById("popup-failed");

          overlay?.classList.remove("d-none");
          popupFail?.classList.remove("d-none");

          popupFail.querySelector(".popup-text").textContent =
            "Falha na requisição: " + err;
        });
    });
  }

  /* =========================================================
     4️⃣ FORMATA DATA
  ========================================================= */

  function formatarData(str) {
    const d = new Date(str);
    if (isNaN(d)) return str;
    return d.toLocaleDateString("pt-BR", { timeZone: "UTC" });
  }

});