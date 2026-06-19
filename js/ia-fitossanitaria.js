(function () {
  "use strict";

  const API_PAINEL = "/funcoes/fitossanitaria/painel.php";
  const API_PERGUNTAR = "/funcoes/fitossanitaria/perguntar.php";
  const API_VALIDAR = "/funcoes/fitossanitaria/validar.php";
  const API_AREAS = "/funcoes/buscar_areas.php";

  const el = {
    area: document.getElementById("ia-fs-area"),
    atualizar: document.getElementById("ia-fs-atualizar"),
    overview: document.getElementById("ia-fs-overview"),
    areaCards: document.getElementById("ia-fs-area-cards"),
    painel: document.getElementById("ia-fs-painel"),
    loading: document.getElementById("ia-fs-loading"),
    erro: document.getElementById("ia-fs-erro"),
    scoreCard: document.getElementById("ia-fs-score-card"),
    scoreBadge: document.getElementById("ia-fs-score-badge"),
    scoreLabel: document.getElementById("ia-fs-score-label"),
    scoreExplicacao: document.getElementById("ia-fs-score-explicacao"),
    scoreMotivos: document.getElementById("ia-fs-score-motivos"),
    diagnostico: document.getElementById("ia-fs-diagnostico"),
    riscoFit: document.getElementById("ia-fs-risco-fit"),
    riscoRes: document.getElementById("ia-fs-risco-res"),
    carencias: document.getElementById("ia-fs-carencias"),
    ia: document.getElementById("ia-fs-ia"),
    cultura: document.getElementById("ia-fs-cultura"),
    csfi: document.getElementById("ia-fs-csfi"),
    historico: document.getElementById("ia-fs-historico"),
    recomendacao: document.getElementById("ia-fs-recomendacao"),
    acao: document.getElementById("ia-fs-acao"),
    validacaoAtual: document.getElementById("ia-fs-validacao-atual"),
    validacaoForm: document.getElementById("ia-fs-validacao-form"),
    validacaoTexto: document.getElementById("ia-fs-validacao-texto"),
    chatLog: document.getElementById("ia-fs-chat-log"),
    chatForm: document.getElementById("ia-fs-chat-form"),
    chatInput: document.getElementById("ia-fs-chat-input"),
  };

  let areaAtual = 0;

  function fmtData(iso) {
    if (!iso) return "—";
    const p = String(iso).split("-");
    if (p.length !== 3) return iso;
    return `${p[2]}/${p[1]}/${p[0]}`;
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function setLoading(on) {
    if (el.loading) el.loading.hidden = !on;
  }

  function showErro(msg) {
    if (!el.erro) return;
    el.erro.hidden = !msg;
    el.erro.textContent = msg || "";
  }

  async function fetchJson(url, opts) {
    const res = await fetch(url, Object.assign({ credentials: "same-origin" }, opts || {}));
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      throw new Error(data.msg || "Falha na comunicação com o servidor.");
    }
    return data;
  }

  function renderTags(container, items, emptyText) {
    if (!container) return;
    container.innerHTML = "";
    if (!items || !items.length) {
      container.textContent = emptyText || "—";
      return;
    }
    items.forEach((t) => {
      const span = document.createElement("span");
      span.className = "ia-fs-tag";
      span.textContent = t;
      container.appendChild(span);
    });
  }

  function renderOverview(areas) {
    if (!el.overview || !el.areaCards) return;
    el.areaCards.innerHTML = "";
    if (!areas || !areas.length) {
      el.overview.hidden = true;
      return;
    }
    el.overview.hidden = false;
    areas.forEach((a) => {
      const card = document.createElement("button");
      card.type = "button";
      card.className = "ia-fs-area-card";
      const sc = a.score || {};
      card.style.setProperty("--score-cor", sc.cor || "#757575");
      card.setAttribute("aria-label", (a.nome || "Área") + " — score " + (sc.nivel || ""));
      card.innerHTML = `
        <div class="ia-fs-area-card-top">
          <span class="ia-fs-area-card-pill">${sc.nivel || "—"}</span>
        </div>
        <span class="ia-fs-area-card-nome">${escapeHtml(a.nome || "Área")}</span>
        <span class="ia-fs-area-card-tipo">${escapeHtml(a.tipo || "área")}</span>
      `;
      card.addEventListener("click", () => {
        if (el.area) {
          el.area.value = String(a.id);
          carregarPainel(a.id);
        }
      });
      el.areaCards.appendChild(card);
    });
  }

  function renderPainel(data) {
    if (!data || !data.ok) return;
    if (el.painel) el.painel.hidden = false;

    const sc = data.score || {};
    if (el.scoreCard) {
      el.scoreCard.style.setProperty("--score-cor", sc.cor || "#757575");
      el.scoreCard.dataset.nivel = sc.nivel || "";
    }
    if (el.scoreBadge) el.scoreBadge.textContent = sc.nivel || "—";
    if (el.scoreLabel) el.scoreLabel.textContent = sc.label || "—";
    if (el.scoreExplicacao) el.scoreExplicacao.textContent = sc.explicacao || "";
    if (el.scoreMotivos) {
      el.scoreMotivos.innerHTML = "";
      (sc.motivos || []).forEach((m) => {
        const li = document.createElement("li");
        li.textContent = m;
        el.scoreMotivos.appendChild(li);
      });
    }

    if (el.diagnostico) el.diagnostico.textContent = data.diagnostico || "—";

    const rf = data.risco_fitossanitario || {};
    if (el.riscoFit) {
      el.riscoFit.textContent = `${rf.resumo || "—"} Nível: ${rf.nivel || "—"}.`;
    }

    const rr = data.risco_residuo || {};
    if (el.riscoRes) {
      el.riscoRes.textContent = `${rr.resumo || "—"} Nível: ${rr.nivel || "—"}.`;
    }

    if (el.carencias) {
      const ativas = (data.status_carencia && data.status_carencia.ativas) || [];
      const pendentes = (data.status_carencia && data.status_carencia.colheitas_pendentes) || [];
      if (!ativas.length && !pendentes.length) {
        el.carencias.textContent = "Nenhuma carência ativa.";
      } else {
        let html = "";
        ativas.forEach((c) => {
          html += `<div class="ia-fs-carencia-item">
            <strong>${c.produto || c.tipo || "Defensivo"}</strong>
            <span>Aplicado ${fmtData(c.data_aplicacao)} · Liberação ${fmtData(c.data_liberacao)} (${c.dias_restantes} dia(s))</span>
          </div>`;
        });
        if (pendentes.length) {
          html += `<p class="ia-fs-sub">Colheitas pendentes:</p>`;
          pendentes.forEach((c) => {
            html += `<div class="ia-fs-carencia-item ia-fs-pendente">Prevista ${fmtData(c.data)}</div>`;
          });
        }
        el.carencias.innerHTML = html;
      }
    }

    renderTags(el.ia, data.produto_ia, "Nenhum ingrediente ativo registrado.");
    renderTags(el.cultura, data.cultura, "Nenhum produto vinculado.");

    const csfi = data.csfi || {};
    if (el.csfi) el.csfi.textContent = csfi.resumo || "—";

    if (el.historico) {
      const hist = data.historico || [];
      if (!hist.length) {
        el.historico.textContent = "Sem aplicações recentes.";
      } else {
        el.historico.innerHTML = hist
          .map(
            (h) => `<div class="ia-fs-hist-row">
              <span class="ia-fs-hist-data">${fmtData(h.data_aplicacao)}</span>
              <span class="ia-fs-hist-prod">${h.produto || h.tipo}</span>
              <span class="ia-fs-hist-meta">${h.carencia_dias ? h.carencia_dias + "d" : "s/ carência"}</span>
            </div>`
          )
          .join("");
      }
    }

    if (el.recomendacao) el.recomendacao.textContent = data.recomendacao || "—";
    if (el.acao) el.acao.textContent = data.acao_sugerida || "—";

    const val = data.validacao_agronomo;
    if (el.validacaoAtual) {
      el.validacaoAtual.textContent = val
        ? `${val.texto} (${fmtData(String(val.criado_em).slice(0, 10))})`
        : "Nenhuma validação registrada.";
    }
  }

  function appendChat(role, text) {
    if (!el.chatLog) return;
    const div = document.createElement("div");
    div.className = "ia-fs-chat-msg ia-fs-chat-" + role;
    div.textContent = text;
    el.chatLog.appendChild(div);
    el.chatLog.scrollTop = el.chatLog.scrollHeight;
  }

  async function carregarAreas() {
    const data = await fetchJson(API_AREAS);
    if (!el.area) return;
    el.area.innerHTML = '<option value="">Selecione uma área...</option>';
    (data || []).forEach((a) => {
      const opt = document.createElement("option");
      opt.value = String(a.id);
      opt.textContent = a.nome + (a.tipo ? " (" + a.tipo + ")" : "");
      el.area.appendChild(opt);
    });
  }

  async function carregarOverview() {
    const data = await fetchJson(API_PAINEL);
    if (data.ok && data.areas) {
      renderOverview(data.areas);
    }
  }

  async function carregarPainel(areaId) {
    areaId = parseInt(areaId, 10);
    if (!areaId) {
      if (el.painel) el.painel.hidden = true;
      return;
    }
    areaAtual = areaId;
    showErro("");
    setLoading(true);
    try {
      const data = await fetchJson(API_PAINEL + "?area_id=" + areaId);
      if (!data.ok) {
        showErro(data.msg || "Não foi possível carregar o painel.");
        return;
      }
      renderPainel(data);
    } catch (e) {
      showErro(e.message || "Erro ao carregar painel.");
    } finally {
      setLoading(false);
    }
  }

  async function enviarPergunta(ev) {
    ev.preventDefault();
    const pergunta = (el.chatInput && el.chatInput.value.trim()) || "";
    if (!pergunta || !areaAtual) return;
    appendChat("user", pergunta);
    if (el.chatInput) el.chatInput.value = "";
    try {
      const data = await fetchJson(API_PERGUNTAR, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ area_id: areaAtual, pergunta }),
      });
      appendChat("bot", data.resposta || data.msg || "Sem resposta.");
    } catch (e) {
      appendChat("bot", e.message || "Erro ao perguntar.");
    }
  }

  async function salvarValidacao(ev) {
    ev.preventDefault();
    const texto = (el.validacaoTexto && el.validacaoTexto.value.trim()) || "";
    if (!texto || !areaAtual) return;
    try {
      const data = await fetchJson(API_VALIDAR, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ area_id: areaAtual, texto }),
      });
      if (data.ok) {
        if (el.validacaoTexto) el.validacaoTexto.value = "";
        await carregarPainel(areaAtual);
      } else {
        showErro(data.msg || "Erro ao salvar.");
      }
    } catch (e) {
      showErro(e.message || "Erro ao salvar validação.");
    }
  }

  function bind() {
    if (el.area) {
      el.area.addEventListener("change", () => carregarPainel(el.area.value));
    }
    if (el.atualizar) {
      el.atualizar.addEventListener("click", () => {
        carregarOverview();
        if (areaAtual) carregarPainel(areaAtual);
      });
    }
    if (el.chatForm) el.chatForm.addEventListener("submit", enviarPergunta);
    if (el.validacaoForm) el.validacaoForm.addEventListener("submit", salvarValidacao);
  }

  async function init() {
    bind();
    setLoading(true);
    try {
      await carregarAreas();
      await carregarOverview();
      const params = new URLSearchParams(window.location.search);
      const areaParam = params.get("area_id");
      if (areaParam && el.area) {
        el.area.value = areaParam;
        await carregarPainel(areaParam);
      }
    } catch (e) {
      showErro(e.message || "Erro ao iniciar.");
    } finally {
      setLoading(false);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
