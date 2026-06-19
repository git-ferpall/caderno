(function () {
  "use strict";

  const API_PAINEL = "/funcoes/fitossanitaria/painel.php";
  const API_PERGUNTAR = "/funcoes/fitossanitaria/perguntar.php";
  const API_PERGUNTAR_AUDIO = "/funcoes/fitossanitaria/perguntar_audio.php";
  const API_VALIDAR = "/funcoes/fitossanitaria/validar.php";
  const API_SYNC_AGROFIT = "/funcoes/fitossanitaria/sincronizar_agrofit.php";
  const API_PDF_LOTE = "/funcoes/fitossanitaria/relatorio_lote.php";
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
    quickQuestions: document.getElementById("ia-fs-quick-questions"),
    swipeHint: document.getElementById("ia-fs-swipe-hint"),
    micBtn: document.getElementById("ia-fs-mic"),
    micLabel: document.getElementById("ia-fs-mic-label"),
    voiceStatus: document.getElementById("ia-fs-voice-status"),
    clima: document.getElementById("ia-fs-clima"),
    agrofit: document.getElementById("ia-fs-agrofit"),
    lote: document.getElementById("ia-fs-lote"),
    loteActions: document.getElementById("ia-fs-lote-actions"),
    pdfLote: document.getElementById("ia-fs-pdf-lote"),
    loteVerificar: document.getElementById("ia-fs-lote-verificar"),
    syncAgrofit: document.getElementById("ia-fs-sync-agrofit"),
  };

  let areaAtual = 0;
  let mediaRecorder = null;
  let audioStream = null;
  let audioChunks = [];
  let gravando = false;
  let preparandoMic = false;
  let processandoPergunta = false;

  function setVoiceStatus(msg, tipo) {
    if (!el.voiceStatus) return;
    el.voiceStatus.textContent = msg;
    el.voiceStatus.className = "ia-fs-voice-status" + (tipo ? " is-" + tipo : "");
  }

  function atualizarMicUi() {
    const habilitado = areaAtual > 0 && !processandoPergunta;
    if (el.micBtn) {
      el.micBtn.disabled = !habilitado || preparandoMic;
      el.micBtn.classList.toggle("is-gravando", gravando);
      el.micBtn.setAttribute("aria-pressed", gravando ? "true" : "false");
    }
    if (el.micLabel) {
      if (!areaAtual) el.micLabel.textContent = "Selecione uma área";
      else if (preparandoMic) el.micLabel.textContent = "Aguardando microfone…";
      else if (gravando) el.micLabel.textContent = "Toque para parar";
      else if (processandoPergunta) el.micLabel.textContent = "Processando…";
      else el.micLabel.textContent = "Toque para falar";
    }
    if (!areaAtual) {
      setVoiceStatus("Selecione uma área para falar sua pergunta");
    } else if (gravando) {
      setVoiceStatus("Gravando… fale sua pergunta agora", "gravando");
    } else if (processandoPergunta) {
      setVoiceStatus("Transcrevendo e analisando…", "processando");
    } else if (!gravando && !preparandoMic) {
      setVoiceStatus("Ex.: “Posso colher hoje nesta área?”");
    }
  }

  function getMicStream() {
    if (!window.isSecureContext) {
      return Promise.reject(Object.assign(new Error("SECURE_CONTEXT"), { name: "SECURE_CONTEXT" }));
    }
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
      return navigator.mediaDevices.getUserMedia({
        audio: { echoCancellation: true, noiseSuppression: true },
      });
    }
    const legacy =
      navigator.getUserMedia ||
      navigator.webkitGetUserMedia ||
      navigator.mozGetUserMedia;
    if (!legacy) {
      return Promise.reject(Object.assign(new Error("UNSUPPORTED"), { name: "UNSUPPORTED" }));
    }
    return new Promise((resolve, reject) => {
      legacy.call(navigator, { audio: true }, resolve, reject);
    });
  }

  function tratarErroMicrofone(err) {
    const nome = (err && (err.name || err.code)) || "";
    if (nome === "SECURE_CONTEXT") {
      showErro("Microfone só funciona em HTTPS.");
      setVoiceStatus("Acesse o site com HTTPS");
      return;
    }
    if (nome === "UNSUPPORTED") {
      showErro("Navegador sem suporte a gravação. Use Chrome ou Safari atualizado.");
      return;
    }
    if (nome === "NotAllowedError" || nome === "PermissionDeniedError" || nome === "SecurityError") {
      showErro("Permita o microfone nas configurações do navegador.");
      setVoiceStatus("Microfone bloqueado — verifique permissões");
      return;
    }
    showErro("Não foi possível acessar o microfone.");
    setVoiceStatus("Erro no microfone — toque para tentar de novo");
  }

  function streamAtivo() {
    return audioStream && audioStream.active && audioStream.getAudioTracks().some((t) => t.readyState === "live");
  }

  function liberarStream() {
    if (audioStream) {
      audioStream.getTracks().forEach((t) => t.stop());
      audioStream = null;
    }
  }

  function criarMediaRecorder(stream) {
    if (typeof MediaRecorder === "undefined") {
      throw new Error("UNSUPPORTED");
    }
    const tipos = [
      "audio/webm;codecs=opus",
      "audio/webm",
      "audio/mp4",
      "audio/ogg;codecs=opus",
      "audio/aac",
      "",
    ];
    for (const mime of tipos) {
      try {
        if (mime && !MediaRecorder.isTypeSupported(mime)) continue;
        return mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream);
      } catch (_) {
        continue;
      }
    }
    return new MediaRecorder(stream);
  }

  function garantirPermissaoMicrofone() {
    if (streamAtivo()) return Promise.resolve(audioStream);
    liberarStream();
    preparandoMic = true;
    atualizarMicUi();
    return getMicStream()
      .then((stream) => {
        audioStream = stream;
        preparandoMic = false;
        atualizarMicUi();
        return stream;
      })
      .catch((err) => {
        preparandoMic = false;
        atualizarMicUi();
        tratarErroMicrofone(err);
        throw err;
      });
  }

  function iniciarGravacao() {
    if (gravando || preparandoMic || processandoPergunta || !areaAtual) return;
    if (typeof MediaRecorder === "undefined") {
      showErro("Seu navegador não suporta gravação de áudio.");
      return;
    }
    garantirPermissaoMicrofone()
      .then((stream) => {
        if (gravando) return;
        mediaRecorder = criarMediaRecorder(stream);
        audioChunks = [];
        mediaRecorder.ondataavailable = (e) => {
          if (e.data && e.data.size > 0) audioChunks.push(e.data);
        };
        mediaRecorder.onerror = () => {
          pararGravacao(false);
          showErro("Erro na gravação. Tente novamente.");
        };
        mediaRecorder.onstop = () => {
          const mime = mediaRecorder.mimeType || "audio/webm";
          enviarAudioPergunta(mime);
        };
        mediaRecorder.start(250);
        gravando = true;
        atualizarMicUi();
      })
      .catch(() => {});
  }

  function pararGravacao(processar) {
    if (!gravando) return;
    gravando = false;
    atualizarMicUi();
    if (mediaRecorder && mediaRecorder.state !== "inactive") {
      try {
        mediaRecorder.stop();
      } catch (_) {
        if (processar !== false) {
          showErro("Gravação muito curta. Tente de novo.");
        }
      }
    } else if (processar !== false) {
      showErro("Gravação muito curta. Tente de novo.");
    }
  }

  function toggleGravacao() {
    if (!areaAtual || processandoPergunta) return;
    if (gravando) pararGravacao();
    else iniciarGravacao();
  }

  async function enviarAudioPergunta(mime) {
    if (!audioChunks.length) {
      showErro("Não captei áudio. Fale mais perto do microfone.");
      setVoiceStatus("Toque para falar de novo");
      return;
    }
    processandoPergunta = true;
    atualizarMicUi();
    const blob = new Blob(audioChunks, { type: mime });
    const fd = new FormData();
    fd.append("audio", blob, "pergunta.webm");
    fd.append("area_id", String(areaAtual));
    try {
      const res = await fetch(API_PERGUNTAR_AUDIO, {
        method: "POST",
        body: fd,
        credentials: "same-origin",
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        throw new Error(data.msg || data.err || "Erro ao processar áudio.");
      }
      tratarRespostaPergunta(data);
    } catch (e) {
      showErro(e.message || "Erro ao enviar áudio.");
      setVoiceStatus("Toque para tentar de novo");
    } finally {
      processandoPergunta = false;
      audioChunks = [];
      atualizarMicUi();
    }
  }

  function tratarRespostaPergunta(data) {
    const pergunta = data.transcricao || data.pergunta || "";
    if (pergunta) appendChat("user", pergunta);
    appendChat("bot", data.resposta || data.msg || "Sem resposta.");
    setVoiceStatus("Toque para fazer outra pergunta");
  }

  function isMobile() {
    return window.matchMedia("(max-width: 768px)").matches;
  }

  function scrollParaPainel() {
    if (!isMobile() || !el.painel || el.painel.hidden) return;
    const alvo = el.scoreCard || el.painel;
    const top = alvo.getBoundingClientRect().top + window.scrollY - 100;
    window.scrollTo({ top: Math.max(0, top), behavior: "smooth" });
  }

  function marcarCardAtivo(areaId) {
    if (!el.areaCards) return;
    el.areaCards.querySelectorAll(".ia-fs-area-card").forEach((card) => {
      card.classList.toggle("is-active", card.dataset.areaId === String(areaId));
    });
  }

  function setupCarousel(areas) {
    if (!el.overview || !el.areaCards) return;
    const usarCarousel = isMobile() && areas && areas.length > 2;
    el.overview.classList.toggle("is-carousel", usarCarousel);
    if (el.swipeHint) el.swipeHint.hidden = !usarCarousel;

    if (usarCarousel && !el.areaCards.dataset.carouselBound) {
      el.areaCards.dataset.carouselBound = "1";
      el.areaCards.addEventListener(
        "scroll",
        () => {
          if (el.swipeHint) el.swipeHint.hidden = true;
        },
        { passive: true, once: true }
      );
    }
  }

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
    let raw = "";
    let data = {};
    try {
      raw = await res.text();
      data = raw ? JSON.parse(raw) : {};
    } catch (_) {
      data = {};
    }
    if (!res.ok) {
      const msg =
        data.msg ||
        data.err ||
        (res.status === 401 ? "Sessão expirada. Faça login novamente." : "") ||
        (raw && raw.length < 280 && !raw.trimStart().startsWith("<") ? raw.trim() : "") ||
        "Falha na comunicação com o servidor (" + res.status + ").";
      throw new Error(msg);
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
    setupCarousel(areas);
    areas.forEach((a) => {
      const card = document.createElement("button");
      card.type = "button";
      card.className = "ia-fs-area-card";
      card.dataset.areaId = String(a.id);
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
          marcarCardAtivo(a.id);
          carregarPainel(a.id);
        }
      });
      el.areaCards.appendChild(card);
    });
    if (areaAtual) marcarCardAtivo(areaAtual);
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
    if (el.csfi) {
      el.csfi.textContent = csfi.resumo || "—";
      el.csfi.classList.toggle("ia-fs-csfi-alert", !!csfi.csfi);
    }

    if (el.clima) {
      const cl = data.clima || {};
      let html = `<p>${escapeHtml(cl.resumo || "—")}</p>`;
      if (cl.recomendacao) html += `<p class="ia-fs-muted">${escapeHtml(cl.recomendacao)}</p>`;
      if (cl.alertas && cl.alertas.length) {
        html += "<ul class='ia-fs-alert-list'>";
        cl.alertas.forEach((a) => { html += `<li>${escapeHtml(a)}</li>`; });
        html += "</ul>";
      }
      el.clima.innerHTML = html;
    }

    if (el.agrofit) {
      const ag = data.agrofit || data.cultura_autorizada || {};
      let html = `<p>${escapeHtml(ag.resumo || "—")}</p>`;
      if (ag.alertas && ag.alertas.length) {
        html += "<ul class='ia-fs-alert-list'>";
        ag.alertas.forEach((a) => { html += `<li>${escapeHtml(a)}</li>`; });
        html += "</ul>";
      }
      el.agrofit.innerHTML = html;
    }

    const loteData = data.lote;
    if (el.lote) {
      if (!loteData) {
        el.lote.textContent = "Lote não disponível (execute migration fase 3).";
        if (el.loteActions) el.loteActions.hidden = true;
      } else {
        el.lote.innerHTML = `
          <div class="ia-fs-lote-grid">
            <div class="ia-fs-lote-info">
              <p><strong>Código:</strong> ${escapeHtml(loteData.codigo_lote)}</p>
              <p><strong>Status:</strong> <span class="ia-fs-lote-status ia-fs-lote-status--${escapeHtml(loteData.status_lote || "")}">${escapeHtml(loteData.status_label || "")}</span></p>
              <p><strong>Hash:</strong> <code class="ia-fs-hash">${escapeHtml(loteData.hash_auditoria || "")}</code></p>
              <p class="ia-fs-muted">Atualizado: ${escapeHtml(loteData.atualizado_em || "")}</p>
            </div>
            ${loteData.url_qrcode ? `<img class="ia-fs-qr" src="${escapeHtml(loteData.url_qrcode)}" width="140" height="140" alt="QR Code auditoria">` : ""}
          </div>`;
        if (el.loteActions) el.loteActions.hidden = false;
        if (el.loteVerificar && loteData.url_verificacao) {
          el.loteVerificar.href = loteData.url_verificacao;
        }
      }
    }

    if (data.aviso_legal && el.painel) {
      let aviso = el.painel.querySelector(".ia-fs-aviso-legal");
      if (!aviso) {
        aviso = document.createElement("p");
        aviso.className = "ia-fs-aviso-legal ia-fs-muted";
        el.painel.appendChild(aviso);
      }
      aviso.textContent = data.aviso_legal;
    }

    if (el.historico) {
      const hist = data.historico || [];
      if (!hist.length) {
        el.historico.textContent = "Sem aplicações recentes.";
      } else {
        el.historico.innerHTML = hist
          .map(
            (h) => `<div class="ia-fs-hist-row">
              <span class="ia-fs-hist-data">${fmtData(h.data_aplicacao)}</span>
              <span class="ia-fs-hist-prod">${escapeHtml(h.produto || h.tipo || "")}</span>
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
      areaAtual = 0;
      atualizarMicUi();
      return;
    }
    areaAtual = areaId;
    atualizarMicUi();
    showErro("");
    setLoading(true);
    try {
      const data = await fetchJson(API_PAINEL + "?area_id=" + areaId);
      if (!data.ok) {
        showErro(data.msg || "Não foi possível carregar o painel.");
        return;
      }
      renderPainel(data);
      marcarCardAtivo(areaId);
      scrollParaPainel();
    } catch (e) {
      showErro(e.message || "Erro ao carregar painel.");
    } finally {
      setLoading(false);
    }
  }

  async function enviarPergunta(ev) {
    if (ev && ev.preventDefault) ev.preventDefault();
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
      setVoiceStatus("Toque para fazer outra pergunta");
    } catch (e) {
      appendChat("bot", e.message || "Erro ao perguntar.");
    }
  }

  function perguntarRapida(pergunta) {
    if (!areaAtual) {
      showErro("Selecione uma área antes de perguntar.");
      return;
    }
    if (el.chatInput) el.chatInput.value = pergunta;
    enviarPergunta(null);
  }

  async function sincronizarAgrofit() {
    if (!el.syncAgrofit) return;
    el.syncAgrofit.disabled = true;
    try {
      const data = await fetchJson(API_SYNC_AGROFIT, { method: "POST" });
      showErro("");
      alert(data.msg || "Sincronizado.");
      if (areaAtual) await carregarPainel(areaAtual);
    } catch (e) {
      showErro(e.message || "Erro ao sincronizar AGROFIT.");
    } finally {
      el.syncAgrofit.disabled = false;
    }
  }

  function gerarPdfLote() {
    if (!areaAtual) return;
    const form = document.createElement("form");
    form.method = "POST";
    form.action = API_PDF_LOTE;
    form.target = "_blank";
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = "area_id";
    input.value = String(areaAtual);
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    form.remove();
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
    if (el.micBtn) el.micBtn.addEventListener("click", toggleGravacao);
    if (el.syncAgrofit) el.syncAgrofit.addEventListener("click", sincronizarAgrofit);
    if (el.pdfLote) el.pdfLote.addEventListener("click", gerarPdfLote);
    window.addEventListener("beforeunload", liberarStream);
    if (el.quickQuestions) {
      el.quickQuestions.addEventListener("click", (ev) => {
        const btn = ev.target.closest(".ia-fs-quick-btn");
        if (!btn) return;
        perguntarRapida(btn.getAttribute("data-q") || btn.textContent || "");
      });
    }
    window.addEventListener("resize", () => {
      if (el.areaCards && el.areaCards.children.length) {
        setupCarousel(Array.from(el.areaCards.children).map((c) => ({ id: c.dataset.areaId })));
      }
    });
  }

  async function init() {
    bind();
    atualizarMicUi();
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
