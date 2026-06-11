(function () {
  'use strict';

  const API_PROCESSAR = '/funcoes/ia/processar_audio.php';
  const API_PROCESSAR_TEXTO = '/funcoes/ia/processar_texto.php';
  const API_BRIEFING = '/funcoes/ia/briefing_agente.php';
  const API_EXECUTAR = '/funcoes/ia/executar_intent.php';

  const SAUDACAO =
    'Oi! Sou seu agente no Caderno Frutag. Posso registrar manejos, consultar pendentes e colheitas, ou marcar como feito. Fale ou digite abaixo.';

  let mediaRecorder = null;
  let audioStream = null;
  let chunks = [];
  let gravando = false;
  let preparandoMic = false;
  let intentPendente = null;
  let intentParcial = null;
  let campoDialogo = null;
  let micPronto = false;
  let falando = false;
  let cardAcaoPendente = null;
  let saudacaoFeita = false;

  const UNIDADES_COLHEITA = [
    { id: 'kg', label: 'kg' },
    { id: 'caixas', label: 'Caixas' },
    { id: 'sacas', label: 'Sacas' },
  ];
  /** Incrementado ao cancelar — impede falarNatural de continuar após parar. */
  let falaSessaoId = 0;
  let falaTimeoutId = null;

  const fab = document.getElementById('assistente-voz-btn');
  const backdrop = document.getElementById('assistente-voz-backdrop');
  const panel = document.getElementById('assistente-voz-panel');
  const btnFechar = document.getElementById('assistente-voz-fechar');
  const btnGravar = document.getElementById('assistente-voz-gravar');
  const btnConfirmar = document.getElementById('assistente-voz-confirmar');
  const btnCancelar = document.getElementById('assistente-voz-cancelar');
  const elStatus = document.getElementById('assistente-voz-status');
  const elHint = document.getElementById('assistente-voz-hint');
  const elChat = document.getElementById('assistente-voz-chat');
  const elDigitando = document.getElementById('assistente-voz-digitando');
  const elConfirmacao = document.getElementById('assistente-voz-confirmacao');
  const elResumo = document.getElementById('assistente-voz-resumo');
  const elGravarTexto = document.getElementById('assistente-voz-gravar-texto');
  const elAvatar = document.getElementById('assistente-voz-avatar');
  const elOndas = document.getElementById('assistente-voz-ondas');
  const elProgresso = document.getElementById('assistente-voz-progresso');
  const elProgressoFill = document.getElementById('assistente-voz-progresso-fill');
  const elProgressoLabel = document.getElementById('assistente-voz-progresso-label');
  const formTexto = document.getElementById('assistente-voz-texto-form');
  const inputTexto = document.getElementById('assistente-voz-texto');
  const btnTextoEnviar = document.getElementById('assistente-voz-texto-enviar');

  if (!fab || !panel) return;

  function setHint(msg, tipo) {
    if (!elHint) return;
    elHint.textContent = msg;
    elHint.className = 'assistente-voz-hint' + (tipo ? ' assistente-voz-hint--' + tipo : '');
  }

  function setStatus(msg) {
    if (elStatus) elStatus.textContent = msg;
  }

  function scrollChat() {
    if (elChat) elChat.scrollTop = elChat.scrollHeight;
  }

  function addMsg(texto, tipo, variante) {
    if (!elChat || !texto) return;
    const div = document.createElement('div');
    const extra = variante && variante !== tipo ? ' assistente-voz-msg--' + variante : '';
    div.className = 'assistente-voz-msg assistente-voz-msg--' + (tipo || 'bot') + extra;
    div.textContent = texto;
    elChat.appendChild(div);
    scrollChat();
  }

  function flashAvatarSucesso() {
    elAvatar?.classList.add('assistente-voz-avatar--sucesso');
    window.setTimeout(() => elAvatar?.classList.remove('assistente-voz-avatar--sucesso'), 900);
  }

  async function lerRespostaApi(resp) {
    const raw = await resp.text();
    if (!raw) {
      if (resp.status === 401) {
        return { ok: false, err: 'Sessão expirada. Faça login de novo.' };
      }
      if (resp.status >= 500) {
        return { ok: false, err: 'Erro interno no servidor (HTTP ' + resp.status + ').' };
      }
      return { ok: false, err: 'Resposta vazia do servidor (HTTP ' + resp.status + ').' };
    }
    try {
      return JSON.parse(raw);
    } catch (_) {
      const trecho = raw.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 160);
      return {
        ok: false,
        err: trecho || 'Resposta inválida do servidor (HTTP ' + resp.status + ').',
      };
    }
  }

  function tratarErroApi(errMsg) {
    showDigitando(false);
    cardAcaoPendente = null;
    addMsg(errMsg, 'bot');
    setHint(errMsg, 'erro');
    falarNatural(errMsg);
  }

  function fecharFormsCards(wrap, exceto) {
    if (!wrap) return;
    wrap.querySelectorAll('.assistente-voz-card-form').forEach((el) => {
      if (exceto && el === exceto) return;
      el.classList.add('d-none');
    });
    wrap.querySelectorAll('.assistente-voz-card--expandido').forEach((el) => {
      if (exceto && el.contains(exceto)) return;
      el.classList.remove('assistente-voz-card--expandido');
      el.querySelector('.assistente-voz-card-detalhe')?.classList.add('d-none');
    });
  }

  function formatarTipoCard(tipo) {
    return (tipo || 'manejo').replace(/_/g, ' ');
  }

  function formatarDataCurta(data) {
    if (!data) return '';
    const m = String(data).match(/^(\d{4})-(\d{2})-(\d{2})/);
    return m ? m[3] + '/' + m[2] + '/' + m[1] : data;
  }

  function rotuloPendenteEscolha(p) {
    const partes = [formatarTipoCard(p.tipo)];
    if (p.produto) partes.push(p.produto);
    if (p.areas) partes.push(p.areas);
    if (p.data) partes.push(formatarDataCurta(p.data));
    return partes.join(' · ');
  }

  function renderEscolhaPendentes(opcoes) {
    if (!elChat || !opcoes?.length) return;

    const wrap = document.createElement('div');
    wrap.className = 'assistente-voz-escolha';
    wrap.setAttribute('role', 'listbox');
    wrap.setAttribute('aria-label', 'Escolha o pendente');

    opcoes.forEach((p, i) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'assistente-voz-escolha-item';
      btn.setAttribute('role', 'option');

      const num = document.createElement('span');
      num.className = 'assistente-voz-escolha-num';
      num.textContent = String(i + 1);

      const txt = document.createElement('span');
      txt.className = 'assistente-voz-escolha-txt';
      txt.textContent = rotuloPendenteEscolha(p);

      btn.append(num, txt);
      btn.addEventListener('click', () => {
        wrap.querySelectorAll('.assistente-voz-escolha-item').forEach((el) => {
          el.disabled = true;
        });
        btn.classList.add('assistente-voz-escolha-item--ativa');
        selecionarPendenteDialogo(p, i);
      });
      wrap.appendChild(btn);
    });

    elChat.appendChild(wrap);
    scrollChat();
  }

  function selecionarPendenteDialogo(p, index) {
    const rotulo = index + 1 + ' — ' + rotuloPendenteEscolha(p);
    enviarComandoTexto(String(index + 1), rotulo);
  }

  function renderConsultaCards(dados) {
    if (!elChat || !dados) return;
    const pendentes = dados.pendentes || dados.amostra;
    if (!pendentes || !pendentes.length) return;

    const wrap = document.createElement('div');
    wrap.className = 'assistente-voz-cards';

    pendentes.slice(0, 6).forEach((p) => {
      const card = document.createElement('article');
      card.className = 'assistente-voz-card';
      card.dataset.id = String(p.id);
      card.dataset.tipo = p.tipo || '';

      const tipo = formatarTipoCard(p.tipo);
      const qtd =
        p.quantidade && Number(p.quantidade) > 0
          ? ' · ' + p.quantidade + (p.unidade ? ' ' + p.unidade : '')
          : '';
      const obs = (p.observacoes || '').trim();

      let corpo =
        '<div class="assistente-voz-card-corpo">' +
        '<strong>' +
        tipo +
        '</strong> — ' +
        (p.produto ? p.produto + ' · ' : '') +
        (p.areas || '—') +
        ' · ' +
        (p.data || '') +
        qtd +
        '</div>';

      if (obs) {
        corpo +=
          '<p class="assistente-voz-card-obs-preview">Obs: ' +
          obs.replace(/</g, '&lt;') +
          '</p>';
      }

      corpo +=
        '<div class="assistente-voz-card-detalhe d-none" aria-live="polite"></div>' +
        '<div class="assistente-voz-card-form d-none" aria-live="polite"></div>';

      card.innerHTML = corpo;

      const actions = document.createElement('div');
      actions.className = 'assistente-voz-card-actions';

      [
        { acao: 'detalhar', label: 'Detalhar' },
        { acao: 'concluir', label: 'Concluir', prim: true },
        { acao: 'editar', label: 'Obs' },
      ].forEach(({ acao, label, prim }) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'assistente-voz-card-btn' + (prim ? ' assistente-voz-card-btn--prim' : '');
        btn.dataset.acao = acao;
        btn.dataset.id = String(p.id);
        btn.textContent = label;
        actions.appendChild(btn);
      });

      card.appendChild(actions);
      card._pendente = p;
      wrap.appendChild(card);
    });

    wrap.addEventListener('click', onCardAcaoClick);
    elChat.appendChild(wrap);
    scrollChat();
  }

  function montarDetalheCard(p) {
    const linhas = [
      ['Tipo', formatarTipoCard(p.tipo)],
      ['Data', p.data || '—'],
      ['Área', p.areas || '—'],
      ['Produto', p.produto || '—'],
    ];
    if (p.quantidade && Number(p.quantidade) > 0) {
      linhas.push(['Quantidade', p.quantidade + (p.unidade ? ' ' + p.unidade : '')]);
    }
    linhas.push(['Observações', (p.observacoes || '').trim() || 'Nenhuma']);

    return (
      '<dl class="assistente-voz-card-dl">' +
      linhas
        .map(
          ([k, v]) =>
            '<div><dt>' + k + '</dt><dd>' + String(v).replace(/</g, '&lt;') + '</dd></div>'
        )
        .join('') +
      '</dl>'
    );
  }

  function abrirFormObs(card, p) {
    const formWrap = card.querySelector('.assistente-voz-card-form');
    if (!formWrap) return;

    fecharFormsCards(card.closest('.assistente-voz-cards'), formWrap);
    formWrap.replaceChildren();

    const label = document.createElement('label');
    label.className = 'assistente-voz-card-form-label';
    label.textContent = 'Observação';

    const ta = document.createElement('textarea');
    ta.className = 'assistente-voz-card-textarea';
    ta.rows = 2;
    ta.maxLength = 500;
    ta.placeholder = 'Digite a observação…';
    ta.value = p.observacoes || '';

    const acoes = document.createElement('div');
    acoes.className = 'assistente-voz-card-form-acoes';

    const btnCancel = document.createElement('button');
    btnCancel.type = 'button';
    btnCancel.className = 'assistente-voz-card-btn';
    btnCancel.dataset.form = 'cancelar';
    btnCancel.textContent = 'Cancelar';

    const btnSalvar = document.createElement('button');
    btnSalvar.type = 'button';
    btnSalvar.className = 'assistente-voz-card-btn assistente-voz-card-btn--prim';
    btnSalvar.dataset.form = 'salvar-obs';
    btnSalvar.textContent = 'Salvar';

    acoes.append(btnCancel, btnSalvar);
    formWrap.append(label, ta, acoes);
    formWrap.classList.remove('d-none');

    ta.focus();
    ta.setSelectionRange(ta.value.length, ta.value.length);
    scrollChat();
  }

  function abrirFormConcluirColheita(card, p) {
    const formWrap = card.querySelector('.assistente-voz-card-form');
    if (!formWrap) return;

    fecharFormsCards(card.closest('.assistente-voz-cards'), formWrap);
    formWrap.replaceChildren();

    const unidadeInicial = (p.unidade && UNIDADES_COLHEITA.some((u) => u.id === p.unidade))
      ? p.unidade
      : 'kg';

    const label = document.createElement('label');
    label.className = 'assistente-voz-card-form-label';
    label.textContent = 'Quanto colheu?';

    const linha = document.createElement('div');
    linha.className = 'assistente-voz-card-form-linha';

    const input = document.createElement('input');
    input.type = 'number';
    input.className = 'assistente-voz-card-input';
    input.min = '0';
    input.step = '0.01';
    input.inputMode = 'decimal';
    input.placeholder = 'Ex: 150';
    if (p.quantidade && Number(p.quantidade) > 0) {
      input.value = String(p.quantidade);
    }

    linha.appendChild(input);

    const unWrap = document.createElement('div');
    unWrap.className = 'assistente-voz-card-unidades';
    unWrap.setAttribute('role', 'group');
    unWrap.setAttribute('aria-label', 'Unidade');

    UNIDADES_COLHEITA.forEach((u) => {
      const pill = document.createElement('button');
      pill.type = 'button';
      pill.className =
        'assistente-voz-card-un' + (u.id === unidadeInicial ? ' assistente-voz-card-un--ativa' : '');
      pill.dataset.unidade = u.id;
      pill.textContent = u.label;
      pill.addEventListener('click', () => {
        unWrap.querySelectorAll('.assistente-voz-card-un').forEach((el) => {
          el.classList.remove('assistente-voz-card-un--ativa');
        });
        pill.classList.add('assistente-voz-card-un--ativa');
      });
      unWrap.appendChild(pill);
    });

    const acoes = document.createElement('div');
    acoes.className = 'assistente-voz-card-form-acoes';

    const btnCancel = document.createElement('button');
    btnCancel.type = 'button';
    btnCancel.className = 'assistente-voz-card-btn';
    btnCancel.dataset.form = 'cancelar';
    btnCancel.textContent = 'Cancelar';

    const btnSalvar = document.createElement('button');
    btnSalvar.type = 'button';
    btnSalvar.className = 'assistente-voz-card-btn assistente-voz-card-btn--prim';
    btnSalvar.dataset.form = 'salvar-qtd';
    btnSalvar.textContent = 'Confirmar colheita';

    acoes.append(btnCancel, btnSalvar);
    formWrap.append(label, linha, unWrap, acoes);
    formWrap.classList.remove('d-none');
    input.focus();
    scrollChat();
  }

  function obterUnidadeColheitaCard(formWrap) {
    return formWrap?.querySelector('.assistente-voz-card-un--ativa')?.dataset.unidade || 'kg';
  }

  function labelUnidadeColheita(un) {
    return UNIDADES_COLHEITA.find((u) => u.id === un)?.label || un;
  }

  function animarCardSucesso(card, opts, onDone) {
    if (!card) {
      if (typeof onDone === 'function') onDone();
      return;
    }
    const remover = opts?.remover !== false;
    card.classList.add('assistente-voz-card--sucesso');

    const check = document.createElement('div');
    check.className = 'assistente-voz-card-check';
    check.setAttribute('aria-hidden', 'true');
    check.innerHTML =
      '<span class="assistente-voz-card-check-icone">✓</span><span class="assistente-voz-card-check-txt">Feito!</span>';
    card.appendChild(check);

    window.setTimeout(() => {
      if (remover) {
        card.classList.add('assistente-voz-card--saindo');
        window.setTimeout(() => {
          card.remove();
          if (typeof onDone === 'function') onDone();
        }, 420);
      } else {
        card.classList.remove('assistente-voz-card--sucesso');
        check.remove();
        if (typeof onDone === 'function') onDone();
      }
    }, remover ? 950 : 700);
  }

  function atualizarObsNoCard(card, obs) {
    if (!card) return;
    let prev = card.querySelector('.assistente-voz-card-obs-preview');
    if (obs) {
      if (!prev) {
        prev = document.createElement('p');
        prev.className = 'assistente-voz-card-obs-preview';
        card.querySelector('.assistente-voz-card-corpo')?.after(prev);
      }
      prev.textContent = 'Obs: ' + obs;
    } else if (prev) {
      prev.remove();
    }
    if (card._pendente) card._pendente.observacoes = obs;
  }

  function onCardAcaoClick(e) {
    const formBtn = e.target.closest('[data-form]');
    if (formBtn) {
      const card = formBtn.closest('.assistente-voz-card');
      const p = card?._pendente;
      const id = parseInt(card?.dataset.id || '0', 10);
      const formWrap = card?.querySelector('.assistente-voz-card-form');
      const acaoForm = formBtn.dataset.form;

      if (acaoForm === 'cancelar') {
        formWrap?.classList.add('d-none');
        return;
      }

      if (acaoForm === 'salvar-obs' && id && formWrap) {
        const obs = formWrap.querySelector('textarea')?.value?.trim() || '';
        if (!obs) return;
        formWrap.classList.add('d-none');
        enviarAcaoRapida(
          { tipo: 'editar_obs', apontamento_id: id, observacoes: obs },
          'Salvar observação — ' + formatarTipoCard(p?.tipo),
          card,
          'editar_obs'
        );
        return;
      }

      if (acaoForm === 'salvar-qtd' && id && formWrap) {
        const qtd = parseFloat(formWrap.querySelector('input')?.value || '0');
        if (!qtd || qtd <= 0) return;
        const un = obterUnidadeColheitaCard(formWrap);
        formWrap.classList.add('d-none');
        enviarAcaoRapida(
          { tipo: 'concluir', apontamento_id: id, quantidade: qtd, unidade: un },
          'Concluir colheita — ' + qtd + ' ' + labelUnidadeColheita(un),
          card,
          'concluir'
        );
      }
      return;
    }

    const btn = e.target.closest('[data-acao]');
    if (!btn) return;

    const card = btn.closest('.assistente-voz-card');
    const p = card?._pendente;
    const id = parseInt(btn.dataset.id, 10);
    const acao = btn.dataset.acao;
    if (!id || !acao || !card) return;

    if (acao === 'detalhar') {
      const det = card.querySelector('.assistente-voz-card-detalhe');
      if (!det || !p) return;
      fecharFormsCards(card.closest('.assistente-voz-cards'));
      const aberto = !det.classList.contains('d-none');
      if (aberto) {
        det.classList.add('d-none');
        card.classList.remove('assistente-voz-card--expandido');
      } else {
        det.innerHTML = montarDetalheCard(p);
        det.classList.remove('d-none');
        card.classList.add('assistente-voz-card--expandido');
      }
      scrollChat();
      return;
    }

    if (acao === 'editar') {
      abrirFormObs(card, p);
      return;
    }

    if (acao === 'concluir') {
      if ((p?.tipo || card.dataset.tipo) === 'colheita') {
        abrirFormConcluirColheita(card, p);
        return;
      }
      enviarAcaoRapida(
        { tipo: 'concluir', apontamento_id: id },
        'Concluir — ' + formatarTipoCard(p?.tipo),
        card,
        'concluir'
      );
    }
  }

  async function enviarAcaoRapida(acaoRapida, rotulo, cardAlvo, tipoAcao) {
    pararFala();
    if (rotulo) addMsg(rotulo, 'user');
    setHint('Processando…', 'processando');
    showDigitando(true);

    cardAcaoPendente = cardAlvo
      ? { card: cardAlvo, tipo: tipoAcao || acaoRapida.tipo, obs: acaoRapida.observacoes }
      : null;

    const payload = { acao_rapida: acaoRapida, texto: rotulo || '' };
    if (intentParcial && campoDialogo) {
      payload.intent_parcial = intentParcial;
      payload.campo_dialogo = campoDialogo;
    }

    try {
      const resp = await fetch(API_PROCESSAR_TEXTO, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await lerRespostaApi(resp);
      if (!data.ok) {
        tratarErroApi(data.err || 'Erro ao processar.');
        return;
      }
      tratarResposta(data);
    } catch (err) {
      console.error(err);
      tratarErroApi('Falha na comunicação. Verifique a internet e tente de novo.');
    }
  }

  function showDigitando(show) {
    elDigitando?.classList.toggle('d-none', !show);
    if (show) scrollChat();
  }

  function showOndas(show) {
    elOndas?.classList.toggle('d-none', !show);
  }

  function setAvatarFalando(ativo) {
    elAvatar?.classList.toggle('assistente-voz-avatar--falando', !!ativo);
  }

  function setGravarBusy(busy) {
    preparandoMic = busy;
    if (btnGravar) btnGravar.disabled = !!busy;
    btnGravar?.classList.toggle('assistente-voz-gravar--busy', !!busy);
  }

  function updateProgresso(passo, total) {
    if (!elProgresso || !passo || !total) {
      elProgresso?.classList.add('d-none');
      return;
    }
    elProgresso.classList.remove('d-none');
    const pct = Math.min(100, Math.round((passo / total) * 100));
    if (elProgressoFill) elProgressoFill.style.width = pct + '%';
    if (elProgressoLabel) elProgressoLabel.textContent = 'Passo ' + passo + ' de ' + total;
  }

  function resetConfirmacao() {
    intentPendente = null;
    elConfirmacao?.classList.add('d-none');
  }

  function resetDialogo() {
    intentParcial = null;
    campoDialogo = null;
    updateProgresso(0, 0);
  }

  function resetChat() {
    if (elChat) elChat.innerHTML = '';
    saudacaoFeita = false;
  }

  function resetTudo() {
    resetConfirmacao();
    resetDialogo();
  }

  function pararFala() {
    falaSessaoId += 1;
    if (falaTimeoutId !== null) {
      clearTimeout(falaTimeoutId);
      falaTimeoutId = null;
    }
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
    }
    falando = false;
    setAvatarFalando(false);
  }

  function falar(texto, onEnd) {
    if (!texto || !('speechSynthesis' in window)) {
      if (typeof onEnd === 'function') onEnd();
      return;
    }

    pararFala();
    const sessao = falaSessaoId;

    const u = new SpeechSynthesisUtterance(texto);
    u.lang = 'pt-BR';
    u.rate = 0.9;
    u.pitch = 1.02;

    const voices = window.speechSynthesis.getVoices();
    const pt = voices.find((v) => v.lang.startsWith('pt-BR')) || voices.find((v) => v.lang.startsWith('pt'));
    if (pt) u.voice = pt;

    u.onstart = () => {
      if (sessao !== falaSessaoId) return;
      setAvatarFalando(true);
    };
    u.onend = () => {
      if (sessao !== falaSessaoId) return;
      falando = false;
      setAvatarFalando(false);
      if (typeof onEnd === 'function') onEnd();
    };
    u.onerror = () => {
      if (sessao !== falaSessaoId) return;
      falando = false;
      setAvatarFalando(false);
      if (typeof onEnd === 'function') onEnd();
    };

    falando = true;
    try {
      window.speechSynthesis.speak(u);
    } catch (_) {
      if (sessao !== falaSessaoId) return;
      falando = false;
      setAvatarFalando(false);
      if (typeof onEnd === 'function') onEnd();
    }
  }

  /** Fala em frases curtas com pausa — soa menos robótico. */
  function falarNatural(texto, onEnd) {
    if (!texto) {
      if (typeof onEnd === 'function') onEnd();
      return;
    }
    const partes = texto
      .split(/(?<=[.?!…])\s+/)
      .map((p) => p.trim())
      .filter(Boolean);
    if (partes.length <= 1 || !('speechSynthesis' in window)) {
      falar(texto, onEnd);
      return;
    }

    pararFala();
    const sessao = falaSessaoId;
    let i = 0;

    function proxima() {
      if (sessao !== falaSessaoId) return;
      if (i >= partes.length) {
        if (typeof onEnd === 'function') onEnd();
        return;
      }
      const frase = partes[i++];
      const u = new SpeechSynthesisUtterance(frase);
      u.lang = 'pt-BR';
      u.rate = 0.9;
      u.pitch = 1.02;
      const voices = window.speechSynthesis.getVoices();
      const pt = voices.find((v) => v.lang.startsWith('pt-BR')) || voices.find((v) => v.lang.startsWith('pt'));
      if (pt) u.voice = pt;

      u.onstart = () => {
        if (sessao !== falaSessaoId) return;
        setAvatarFalando(true);
        falando = true;
      };
      u.onend = () => {
        if (sessao !== falaSessaoId) return;
        falando = false;
        setAvatarFalando(false);
        falaTimeoutId = setTimeout(proxima, 320);
      };
      u.onerror = () => {
        if (sessao !== falaSessaoId) return;
        falando = false;
        setAvatarFalando(false);
        falaTimeoutId = setTimeout(proxima, 320);
      };

      falando = true;
      try {
        window.speechSynthesis.speak(u);
      } catch (_) {
        if (sessao !== falaSessaoId) return;
        falando = false;
        setAvatarFalando(false);
      }
    }
    proxima();
  }

  if ('speechSynthesis' in window) {
    window.speechSynthesis.getVoices();
    window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
  }

  function saudarSeNecessario() {
    if (saudacaoFeita) return;
    saudacaoFeita = true;
    addMsg(SAUDACAO, 'bot');
    falarNatural(SAUDACAO);
  }

  function autoGravarResposta() {
    setTimeout(() => {
      if (intentParcial && !gravando && !preparandoMic) {
        iniciarGravacao();
      }
    }, 700);
  }

  function tratarResposta(data) {
    showDigitando(false);

    if (data.transcricao) {
      addMsg(data.transcricao, 'user');
    }

    const cardRef = data.executado ? cardAcaoPendente : null;
    if (!data.executado) {
      cardAcaoPendente = null;
    }

    if (data.executado) {
      const msg = data.fala || data.msg || 'Pronto! Manejo registrado.';
      cardAcaoPendente = null;

      function finalizarSucesso() {
        resetTudo();
        addMsg(msg, 'bot', cardRef ? 'sucesso' : null);
        if (data.consulta_dados) renderConsultaCards(data.consulta_dados);
        setHint(msg, 'sucesso');
        falarNatural(msg);
        notificarAtualizacao();
        flashAvatarSucesso();
      }

      if (cardRef?.card) {
        if (cardRef.tipo === 'editar_obs' && cardRef.obs) {
          atualizarObsNoCard(cardRef.card, cardRef.obs);
        }
        animarCardSucesso(cardRef.card, { remover: cardRef.tipo === 'concluir' }, finalizarSucesso);
      } else {
        finalizarSucesso();
      }
      return;
    }

    if (data.precisa_dialogo && data.intent_parcial) {
      intentParcial = data.intent_parcial;
      campoDialogo = data.campo_dialogo || null;
      const pergunta = data.pergunta || data.msg || 'Preciso de mais uma informação.';
      const fala = data.fala || pergunta;

      updateProgresso(data.dialogo_passo, data.dialogo_total);
      resetConfirmacao();
      addMsg(pergunta, 'bot');

      if (campoDialogo === 'pendente_escolha' && data.intent_parcial._pendentes_opcao?.length) {
        renderEscolhaPendentes(data.intent_parcial._pendentes_opcao);
        setHint('Toque na opção ou diga o número (ex: 1, 2…)', 'dialogo');
      } else {
        setHint('Responda abaixo ou toque para falar', 'dialogo');
      }

      falarNatural(fala, autoGravarResposta);
      return;
    }

    if (data.precisa_confirmacao && data.intent) {
      resetDialogo();
      intentPendente = data.intent;
      const resumo = data.resumo || data.msg || 'Confirmar ação?';
      if (elResumo) elResumo.textContent = resumo;
      elConfirmacao?.classList.remove('d-none');
      updateProgresso(0, 0);

      const fala = data.fala || 'Pronto, anotei tudo. ' + resumo + ' Confirmo e salvo?';
      addMsg(fala, 'bot');
      setHint('Confirme ou toque para corrigir', 'confirmacao');
      falarNatural(fala);
      return;
    }

    resetDialogo();
    const errMsg = data.msg || data.intent?.mensagem || 'Não entendi. Tente reformular.';
    addMsg(errMsg, 'bot');
    setHint(errMsg, 'erro');
    falarNatural(errMsg);
  }

  function mensagemMicrofoneBloqueado() {
    return 'Microfone bloqueado. Toque no cadeado na barra do navegador, permita o microfone e recarregue a página.';
  }

  function getMicStream() {
    if (!window.isSecureContext) {
      return Promise.reject(Object.assign(new Error('SECURE_CONTEXT'), { name: 'SECURE_CONTEXT' }));
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
      return Promise.reject(Object.assign(new Error('UNSUPPORTED'), { name: 'UNSUPPORTED' }));
    }

    return new Promise((resolve, reject) => {
      legacy.call(navigator, { audio: true }, resolve, reject);
    });
  }

  function tratarErroMicrofone(err) {
    console.error('[assistente-voz]', err);
    const nome = (err && (err.name || err.code)) || '';

    if (nome === 'SECURE_CONTEXT') {
      addMsg('O microfone só funciona em HTTPS.', 'bot');
      setHint('Acesse o site com HTTPS', 'erro');
      return;
    }
    if (nome === 'UNSUPPORTED') {
      addMsg('Seu navegador não suporta gravação. Use Chrome ou Safari atualizado.', 'bot');
      setHint('Navegador incompatível', 'erro');
      return;
    }
    if (nome === 'NotFoundError' || nome === 'DevicesNotFoundError') {
      addMsg('Nenhum microfone encontrado neste aparelho.', 'bot');
      setHint('Microfone não encontrado', 'erro');
      return;
    }
    if (nome === 'NotReadableError' || nome === 'TrackStartError') {
      addMsg('Microfone em uso por outro app. Feche e tente de novo.', 'bot');
      setHint('Microfone ocupado', 'erro');
      return;
    }
    if (nome === 'NotAllowedError' || nome === 'PermissionDeniedError' || nome === 'SecurityError') {
      addMsg(mensagemMicrofoneBloqueado(), 'bot');
      setHint('Permita o microfone nas configurações', 'erro');
      return;
    }
    addMsg('Não foi possível acessar o microfone. Tente novamente.', 'bot');
    setHint('Erro no microfone — toque para tentar de novo', 'erro');
  }

  function streamAtivo() {
    return audioStream && audioStream.active && audioStream.getAudioTracks().some((t) => t.readyState === 'live');
  }

  function liberarStream() {
    if (audioStream) {
      audioStream.getTracks().forEach((t) => t.stop());
      audioStream = null;
    }
    micPronto = false;
  }

  /** iOS exige getUserMedia direto no clique — sem await antes. */
  function garantirPermissaoMicrofone() {
    if (streamAtivo()) {
      micPronto = true;
      return Promise.resolve(audioStream);
    }

    liberarStream();
    setGravarBusy(true);
    setHint('Aguardando permissão do microfone…', 'processando');

    return getMicStream()
      .then((stream) => {
        audioStream = stream;
        micPronto = true;
        setGravarBusy(false);
        setHint('Microfone pronto — toque para falar', 'sucesso');
        return stream;
      })
      .catch((err) => {
        setGravarBusy(false);
        micPronto = false;
        tratarErroMicrofone(err);
        throw err;
      });
  }

  function criarMediaRecorder(stream) {
    if (typeof MediaRecorder === 'undefined') {
      throw new Error('UNSUPPORTED');
    }

    const tipos = [
      'audio/webm;codecs=opus',
      'audio/webm',
      'audio/mp4',
      'audio/ogg;codecs=opus',
      'audio/aac',
      '',
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

  function abrirPanel() {
    panel.classList.remove('d-none');
    backdrop?.classList.remove('d-none');
    fab.classList.add('assistente-voz-fab--oculto');
    fab.setAttribute('aria-expanded', 'true');
    document.body.classList.add('assistente-voz-aberto');
    carregarBriefingSeNecessario().then(() => saudarSeNecessario());
    setHint('Fale ou digite seu comando', '');
  }

  function fecharPanel() {
    panel.classList.add('d-none');
    backdrop?.classList.add('d-none');
    fab.classList.remove('assistente-voz-fab--oculto');
    fab.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('assistente-voz-aberto');
    pararGravacao();
    pararFala();
    resetTudo();
    resetChat();
    showDigitando(false);
    showOndas(false);
    setGravarBusy(false);
    setHint('Toque no botão laranja e fale seu comando', '');
  }

  function notificarAtualizacao() {
    if (typeof window.carregarManejos === 'function') {
      window.carregarManejos();
    } else if (/apontamento\.php/i.test(window.location.pathname)) {
      window.location.reload();
    }
    document.dispatchEvent(new CustomEvent('caderno:apontamento-atualizado'));
  }

  async function enviarComandoTexto(texto, rotuloExibicao) {
    const t = (texto || '').trim();
    if (!t) return;

    pararFala();
    addMsg(rotuloExibicao || t, 'user');
    setHint('Processando…', 'processando');
    showDigitando(true);

    const payload = { texto: t };
    if (intentParcial && campoDialogo) {
      payload.intent_parcial = intentParcial;
      payload.campo_dialogo = campoDialogo;
    }

    try {
      const resp = await fetch(API_PROCESSAR_TEXTO, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await lerRespostaApi(resp);
      if (!data.ok) {
        tratarErroApi(data.err || 'Erro ao processar.');
        return;
      }
      tratarResposta(data);
    } catch (err) {
      console.error(err);
      tratarErroApi('Falha na comunicação. Verifique a internet e tente de novo.');
    }
  }

  async function carregarBriefingSeNecessario() {
    const chave = 'assistente_briefing_' + new Date().toISOString().slice(0, 10);
    if (sessionStorage.getItem(chave)) return;

    try {
      const resp = await fetch(API_BRIEFING, { credentials: 'same-origin' });
      const data = await resp.json();
      if (data.ok && data.msg) {
        sessionStorage.setItem(chave, '1');
        addMsg(data.msg, 'bot');
        falarNatural(data.msg);
        saudacaoFeita = true;
      }
    } catch (_) {
      /* briefing opcional */
    }
  }

  function iniciarGravacao() {
    if (gravando || preparandoMic) return;

    pararFala();

    if (!intentParcial && !intentPendente) {
      resetConfirmacao();
    }

    if (typeof MediaRecorder === 'undefined') {
      addMsg('Seu navegador não suporta gravação de áudio.', 'bot');
      setHint('Atualize o navegador', 'erro');
      return;
    }

    garantirPermissaoMicrofone()
      .then((stream) => {
        if (gravando) return;

        mediaRecorder = criarMediaRecorder(stream);
        chunks = [];

        mediaRecorder.ondataavailable = (e) => {
          if (e.data && e.data.size > 0) chunks.push(e.data);
        };

        mediaRecorder.onerror = (e) => {
          console.error('[assistente-voz] recorder', e);
          pararGravacao();
          setHint('Erro na gravação — tente de novo', 'erro');
        };

        mediaRecorder.onstop = () => {
          const mime = mediaRecorder.mimeType || 'audio/webm';
          enviarAudio(mime);
        };

        try {
          mediaRecorder.start(250);
        } catch (err) {
          console.error('[assistente-voz] start', err);
          addMsg('Não foi possível iniciar a gravação.', 'bot');
          setHint('Toque para tentar de novo', 'erro');
          return;
        }

        gravando = true;
        btnGravar?.classList.add('assistente-voz-gravar--ativo');
        btnGravar?.setAttribute('aria-pressed', 'true');
        showOndas(true);
        if (elGravarTexto) elGravarTexto.textContent = 'Toque para parar';
        setHint('Gravando… fale agora', 'gravando');
      })
      .catch(() => {
        /* erro já tratado */
      });
  }

  function pararGravacao() {
    if (!gravando) return;

    gravando = false;
    btnGravar?.classList.remove('assistente-voz-gravar--ativo');
    btnGravar?.setAttribute('aria-pressed', 'false');
    showOndas(false);
    if (elGravarTexto) elGravarTexto.textContent = 'Toque para falar';

    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      try {
        mediaRecorder.stop();
      } catch (_) {
        /* ignore */
      }
    } else {
      setHint('Gravação muito curta — tente de novo', 'erro');
    }
  }

  async function enviarAudio(mime) {
    setHint('Processando áudio…', 'processando');
    showDigitando(true);

    if (!chunks.length) {
      showDigitando(false);
      addMsg('Não captei áudio. Segure o botão e fale mais perto do microfone.', 'bot');
      setHint('Toque para falar de novo', 'erro');
      return;
    }

    setGravarBusy(true);

    const blob = new Blob(chunks, { type: mime });
    const fd = new FormData();
    fd.append('audio', blob, 'comando.webm');

    if (intentParcial && campoDialogo) {
      fd.append('intent_parcial', JSON.stringify(intentParcial));
      fd.append('campo_dialogo', campoDialogo);
    }

    try {
      const resp = await fetch(API_PROCESSAR, { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await lerRespostaApi(resp);

      if (!data.ok) {
        tratarErroApi(data.err || 'Erro ao processar áudio.');
        return;
      }

      tratarResposta(data);
    } catch (err) {
      console.error(err);
      tratarErroApi('Falha na comunicação com o servidor. Verifique a internet e tente de novo.');
    } finally {
      setGravarBusy(false);
    }
  }

  async function confirmarIntent() {
    if (!intentPendente) return;

    showDigitando(true);
    setHint('Salvando…', 'processando');
    btnConfirmar.disabled = true;
    pararFala();

    try {
      const resp = await fetch(API_EXECUTAR, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ intent: intentPendente }),
      });
      const data = await resp.json();
      showDigitando(false);

      if (data.ok && data.executado) {
        const msg = data.msg || 'Manejo registrado com sucesso!';
        resetTudo();
        elConfirmacao?.classList.add('d-none');
        addMsg(msg, 'bot');
        setHint(msg, 'sucesso');
        falarNatural(msg);
        notificarAtualizacao();
      } else {
        const errMsg = data.msg || data.err || 'Não foi possível executar.';
        addMsg(errMsg, 'bot');
        setHint(errMsg, 'erro');
      }
    } catch (err) {
      console.error(err);
      showDigitando(false);
      addMsg('Falha ao salvar.', 'bot');
      setHint('Erro ao salvar', 'erro');
    } finally {
      btnConfirmar.disabled = false;
    }
  }

  fab.addEventListener('click', () => {
    if (panel.classList.contains('d-none')) {
      abrirPanel();
    } else {
      fecharPanel();
    }
  });

  backdrop?.addEventListener('click', fecharPanel);
  btnFechar?.addEventListener('click', fecharPanel);

  btnGravar?.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    pararFala();
    if (gravando) pararGravacao();
    else iniciarGravacao();
  });

  formTexto?.addEventListener('submit', (e) => {
    e.preventDefault();
    const t = inputTexto?.value || '';
    if (inputTexto) inputTexto.value = '';
    enviarComandoTexto(t);
  });

  btnConfirmar?.addEventListener('click', confirmarIntent);

  btnCancelar?.addEventListener('click', () => {
    pararFala();
    resetTudo();
    elConfirmacao?.classList.add('d-none');
    addMsg('Ok, vamos recomeçar. Toque para falar o manejo.', 'bot');
    setHint('Toque para falar de novo', '');
    falarNatural('Ok, vamos recomeçar.');
  });

  window.addEventListener('pagehide', () => {
    liberarStream();
    pararFala();
  });
})();
