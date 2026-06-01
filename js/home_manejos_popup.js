document.addEventListener("DOMContentLoaded", () => {

  const overlay = document.getElementById("popup-overlay");
  const popupDetalhe = document.getElementById("popup-detalhe-manejo");
  const btnConcluir = document.getElementById("btn-marcar-concluido");
  const btnEditar = document.getElementById("btn-editar-manejo");
  const btnSalvar = document.getElementById("btn-salvar-manejo");
  const btnCancelarEdicao = document.getElementById("btn-cancelar-edicao-manejo");
  const btnHistorico = document.getElementById("btn-ver-historico");
  const historicoWrap = document.getElementById("manejo-historico-wrap");
  const historicoList = document.getElementById("manejo-historico-list");

  let apontamentoAtual = null;
  let modoEdicao = false;

  const tiposComQuantidade = [
    "colheita", "irrigacao", "fertilizante", "herbicida", "fungicida", "inseticida"
  ];

  /* =========================================================
     CLIQUE NAS LINHAS (delegação — pendentes e concluídos)
  ========================================================= */

  document.addEventListener("click", (e) => {
    const tr = e.target.closest(".apontamento-fazer tbody tr, .apontamento-concluido tbody tr");
    if (!tr?.dataset?.id) return;
    abrirPopupManejo(tr.dataset.id);
  });

  window.inicializarPopupLinhas = function () {};

  function setModoEdicao(ativo) {
    modoEdicao = ativo;

    const inputData = document.getElementById("manejo-data");
    const inputObs = document.getElementById("manejo-obs");
    const inputQtd = document.getElementById("colheita-quantidade");
    const inputUn = document.getElementById("colheita-unidade");
    const blocoQtd = document.getElementById("bloco-colheita");

    inputData.readOnly = !ativo;
    inputObs.readOnly = !ativo;

    const qtdParaConcluir = !ativo
      && apontamentoAtual?.status !== "concluido"
      && apontamentoAtual?.tipo === "colheita"
      && blocoQtd && !blocoQtd.classList.contains("d-none");

    inputQtd.readOnly = ativo ? false : !qtdParaConcluir;
    inputUn.disabled = ativo ? false : !qtdParaConcluir;

    btnEditar?.classList.toggle("d-none", ativo);
    btnSalvar?.classList.toggle("d-none", !ativo);
    btnCancelarEdicao?.classList.toggle("d-none", !ativo);
  }

  function preencherPopup(a) {
    apontamentoAtual = a;
    setModoEdicao(false);
    historicoWrap?.classList.add("d-none");

    const blocoQtd = document.getElementById("bloco-colheita");
    const mostrarQtd = tiposComQuantidade.includes(a.tipo) || (a.quantidade !== null && a.quantidade !== "");

    if (mostrarQtd) {
      blocoQtd?.classList.remove("d-none");
      document.getElementById("colheita-quantidade").value = a.quantidade ?? "";
      document.getElementById("colheita-unidade").value = a.unidade || "kg";
    } else {
      blocoQtd?.classList.add("d-none");
    }

    document.getElementById("manejo-data").value = (a.data || "").substring(0, 10);
    document.getElementById("manejo-tipo").value = a.tipo;

    const statusEl = document.getElementById("manejo-status");
    const st = (a.status || "").toLowerCase();
    statusEl.textContent = st === "concluido" ? "Concluído" : "Pendente";
    statusEl.className = "manejo-status-badge " + (st === "concluido" ? "concluido" : "pendente");

    document.getElementById("manejo-obs").value = a.observacoes || "";

    document.getElementById("manejo-areas").innerHTML =
      (a.areas || []).map((n) => `• ${n}`).join("<br>") || "—";

    document.getElementById("manejo-produtos").innerHTML =
      (a.produtos || []).map((n) => `• ${n}`).join("<br>") || "—";

    const extras = Object.entries(a.detalhes || {})
      .map(([k, v]) => `<div><b>${k}:</b> ${v}</div>`)
      .join("");

    document.getElementById("manejo-detalhes-extra").innerHTML =
      extras || "<em>Sem detalhes adicionais.</em>";

    if (btnConcluir) {
      btnConcluir.dataset.id = a.id;
      btnConcluir.classList.toggle("d-none", a.status === "concluido");
    }
  }

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

        preencherPopup(data.apontamento);
        overlay?.classList.remove("d-none");
        popupDetalhe?.classList.remove("d-none");
      })
      .catch((err) => alert("Erro: " + err));
  }

  function carregarHistorico(id) {
    fetch("../funcoes/buscar_historico_apontamento.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then((r) => r.json())
      .then((data) => {
        if (!data.ok) {
          alert(data.msg || "Erro ao carregar histórico.");
          return;
        }

        historicoWrap?.classList.remove("d-none");

        if (!data.historico || data.historico.length === 0) {
          historicoList.innerHTML = "<em>Nenhuma alteração registrada.</em>";
          return;
        }

        historicoList.innerHTML = data.historico.map((h) => `
          <div class="manejo-historico-item">
            <strong>${h.alterado_em}</strong> — ${h.campo}<br>
            <span style="color:#777;">${h.valor_anterior}</span>
            →
            <span style="color:#4a7c1b;">${h.valor_novo}</span>
          </div>
        `).join("");
      })
      .catch((err) => alert("Erro: " + err));
  }

  btnEditar?.addEventListener("click", () => {
    if (!apontamentoAtual) return;
    setModoEdicao(true);
  });

  btnCancelarEdicao?.addEventListener("click", () => {
    if (!apontamentoAtual) return;
    preencherPopup(apontamentoAtual);
  });

  btnSalvar?.addEventListener("click", () => {
    if (!apontamentoAtual) return;

    const params = new URLSearchParams();
    params.append("id", apontamentoAtual.id);
    params.append("data", document.getElementById("manejo-data").value);
    params.append("observacoes", document.getElementById("manejo-obs").value);

    const blocoQtd = document.getElementById("bloco-colheita");
    if (blocoQtd && !blocoQtd.classList.contains("d-none")) {
      params.append("quantidade", document.getElementById("colheita-quantidade").value);
      params.append("unidade", document.getElementById("colheita-unidade").value);
    }

    fetch("../funcoes/editar_apontamento.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params.toString(),
    })
      .then((r) => r.json())
      .then((res) => {
        if (!res.ok) {
          alert(res.msg || "Erro ao salvar.");
          return;
        }

        alert(res.msg || "Salvo com sucesso!");
        abrirPopupManejo(apontamentoAtual.id);
        if (typeof window.carregarManejos === "function") {
          window.carregarManejos();
        }
      })
      .catch((err) => alert("Erro: " + err));
  });

  btnHistorico?.addEventListener("click", () => {
    if (!apontamentoAtual) return;
    carregarHistorico(apontamentoAtual.id);
  });

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

      if (blocoColheita && !blocoColheita.classList.contains("d-none")) {
        quantidade = document.getElementById("colheita-quantidade").value;
        unidade = document.getElementById("colheita-unidade").value;

        if (!quantidade || quantidade <= 0) {
          alert("Informe a quantidade colhida.");
          return;
        }
      }

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
            popupSucesso.querySelector("#btn-ok").onclick = function () {
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
});
