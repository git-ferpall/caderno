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
  const anexosList = document.getElementById("manejo-anexos-list");
  const anexoInput = document.getElementById("manejo-anexo-input");

  let apontamentoAtual = null;
  let modoEdicao = false;

  const tiposComQuantidade = [
    "colheita", "semeadura", "irrigacao", "fertilizante", "herbicida", "fungicida", "inseticida"
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

  function ocultarPopupsOverlay() {
    document.querySelectorAll("#popup-overlay .popup-box").forEach((el) => {
      el.classList.add("d-none");
    });
  }

  function reabrirDetalheManejo() {
    overlay?.classList.remove("d-none");
    popupDetalhe?.classList.remove("d-none");
  }

  function showPopupSucesso(mensagem, onOk) {
    ocultarPopupsOverlay();
    overlay?.classList.remove("d-none");
    const popup = document.getElementById("popup-success");
    popup?.classList.remove("d-none");
    const title = popup?.querySelector(".popup-title");
    if (title) title.textContent = mensagem;
    const btnOk = popup?.querySelector(".popup-btn");
    if (btnOk) {
      btnOk.onclick = () => {
        popup?.classList.add("d-none");
        if (typeof onOk === "function") onOk();
      };
    }
  }

  function showPopupErro(mensagem, reabrirDetalhe = true) {
    ocultarPopupsOverlay();
    overlay?.classList.remove("d-none");
    const popup = document.getElementById("popup-failed");
    popup?.classList.remove("d-none");
    const text = popup?.querySelector(".popup-text");
    if (text) text.textContent = mensagem;
    const btn = popup?.querySelector(".popup-btn");
    if (btn) {
      btn.onclick = () => {
        popup?.classList.add("d-none");
        if (reabrirDetalhe && apontamentoAtual) {
          reabrirDetalheManejo();
        } else if (typeof closePopup === "function") {
          closePopup();
        }
      };
    }
  }

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

    const detalheLabels = {
      variedade: "Variedade / cultivar",
      tipo_semeadura: "Tipo de semeadura",
      bancada_nome: "Bancada",
      destino: "Destino",
    };

    const extras = Object.entries(a.detalhes || {})
      .map(([k, v]) => `<div><b>${detalheLabels[k] || k.replace(/_/g, " ")}:</b> ${v}</div>`)
      .join("");

    document.getElementById("manejo-detalhes-extra").innerHTML =
      extras || "<em>Sem detalhes adicionais.</em>";

    if (btnConcluir) {
      btnConcluir.dataset.id = a.id;
      btnConcluir.classList.toggle("d-none", a.status === "concluido");
    }

    renderAnexos(a.arquivos || []);
  }

  function renderAnexos(arquivos) {
    if (!anexosList) return;
    if (!arquivos.length) {
      anexosList.innerHTML = '<p class="manejo-anexos-empty">Nenhum anexo vinculado.</p>';
      return;
    }
    anexosList.innerHTML = arquivos.map((arq) => `
      <div class="manejo-anexo-item" data-vinculo="${arq.vinculo_id}">
        <a href="../funcoes/silo/download_arquivo.php?id=${arq.id}" target="_blank" rel="noopener">${arq.nome_arquivo}</a>
        <div class="manejo-anexo-actions">
          <button type="button" class="btn-remove" data-vinculo="${arq.vinculo_id}">Remover</button>
        </div>
      </div>
    `).join("");
  }

  function carregarAnexos(id) {
    fetch(`../funcoes/apontamento_arquivo_acao.php?acao=listar&apontamento_id=${encodeURIComponent(id)}`)
      .then((r) => r.json())
      .then((data) => {
        if (data.ok) renderAnexos(data.arquivos || []);
      })
      .catch(() => {});
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
          showPopupErro(data.msg || "Erro ao buscar detalhes.", false);
          return;
        }

        preencherPopup(data.apontamento);
        overlay?.classList.remove("d-none");
        popupDetalhe?.classList.remove("d-none");
      })
      .catch((err) => showPopupErro("Erro ao buscar detalhes: " + err, false));
  }

  window.abrirPopupManejo = abrirPopupManejo;

  anexosList?.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-remove");
    if (!btn || !apontamentoAtual) return;
    const vinculoId = btn.dataset.vinculo;
    if (!vinculoId) return;

    const fd = new FormData();
    fd.append("acao", "desvincular");
    fd.append("vinculo_id", vinculoId);

    fetch("../funcoes/apontamento_arquivo_acao.php", { method: "POST", body: fd })
      .then((r) => r.json())
      .then((res) => {
        if (res.ok) carregarAnexos(apontamentoAtual.id);
        else showPopupErro(res.msg || "Erro ao remover anexo.");
      });
  });

  anexoInput?.addEventListener("change", () => {
    if (!apontamentoAtual || !anexoInput.files?.length) return;

    const fd = new FormData();
    fd.append("acao", "upload");
    fd.append("apontamento_id", apontamentoAtual.id);
    fd.append("arquivo", anexoInput.files[0]);

    fetch("../funcoes/apontamento_arquivo_acao.php", { method: "POST", body: fd })
      .then((r) => r.json())
      .then((res) => {
        anexoInput.value = "";
        if (res.ok) {
          carregarAnexos(apontamentoAtual.id);
        } else {
          showPopupErro(res.msg || "Erro ao anexar arquivo.");
        }
      })
      .catch(() => showPopupErro("Falha ao enviar anexo."));
  });

  function carregarHistorico(id) {
    fetch("../funcoes/buscar_historico_apontamento.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then((r) => r.json())
      .then((data) => {
        if (!data.ok) {
          showPopupErro(data.msg || "Erro ao carregar histórico.");
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
      .catch((err) => showPopupErro("Erro ao carregar histórico: " + err));
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
          showPopupErro(res.msg || "Erro ao salvar.");
          return;
        }

        showPopupSucesso(res.msg || "Apontamento atualizado com sucesso!", () => {
          reabrirDetalheManejo();
          abrirPopupManejo(apontamentoAtual.id);
          if (typeof window.carregarManejos === "function") {
            window.carregarManejos();
          }
        });
      })
      .catch((err) => showPopupErro("Erro ao salvar: " + err));
  });

  btnHistorico?.addEventListener("click", () => {
    if (!apontamentoAtual) return;
    carregarHistorico(apontamentoAtual.id);
  });

  if (btnConcluir) {
    btnConcluir.addEventListener("click", () => {
      const id = btnConcluir.dataset.id;
      if (!id) {
        showPopupErro("ID inválido.");
        return;
      }

      let quantidade = null;
      let unidade = null;
      const blocoColheita = document.getElementById("bloco-colheita");

      if (blocoColheita && !blocoColheita.classList.contains("d-none")) {
        quantidade = document.getElementById("colheita-quantidade").value;
        unidade = document.getElementById("colheita-unidade").value;

        if (!quantidade || quantidade <= 0) {
          showPopupErro("Informe a quantidade colhida.");
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
            ocultarPopupsOverlay();
            overlay?.classList.remove("d-none");
            popupSucesso?.classList.remove("d-none");
            popupSucesso.querySelector(".popup-title").textContent =
              "Manejo concluído com sucesso!";
            const btnOk = popupSucesso.querySelector(".popup-btn");
            if (btnOk) {
              btnOk.onclick = function () {
                if (typeof closePopup === "function") closePopup();
                popupSucesso?.classList.add("d-none");
                location.reload();
              };
            }
          } else {
            showPopupErro(res.msg || "Erro ao concluir.");
          }
        })
        .catch((err) => {
          showPopupErro("Falha na requisição: " + err);
        });
    });
  }
});
