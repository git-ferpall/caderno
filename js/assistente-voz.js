(function () {
  'use strict';

  const API_PROCESSAR = '/funcoes/ia/processar_audio.php';
  const API_EXECUTAR = '/funcoes/ia/executar_intent.php';

  const SAUDACAO =
    'Oi! Sou seu agente no Caderno Frutag. Posso registrar manejos ou consultar o caderno — por exemplo: quantos pendentes tenho, ou quanto colhi na última colheita.';

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
  let saudacaoFeita = false;

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

  function addMsg(texto, tipo) {
    if (!elChat || !texto) return;
    const div = document.createElement('div');
    div.className = 'assistente-voz-msg assistente-voz-msg--' + (tipo || 'bot');
    div.textContent = texto;
    elChat.appendChild(div);
    scrollChat();
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
    const u = new SpeechSynthesisUtterance(texto);
    u.lang = 'pt-BR';
    u.rate = 0.9;
    u.pitch = 1.02;

    const voices = window.speechSynthesis.getVoices();
    const pt = voices.find((v) => v.lang.startsWith('pt-BR')) || voices.find((v) => v.lang.startsWith('pt'));
    if (pt) u.voice = pt;

    u.onstart = () => setAvatarFalando(true);
    u.onend = () => {
      falando = false;
      setAvatarFalando(false);
      if (typeof onEnd === 'function') onEnd();
    };
    u.onerror = () => {
      falando = false;
      setAvatarFalando(false);
      if (typeof onEnd === 'function') onEnd();
    };

    falando = true;
    try {
      window.speechSynthesis.speak(u);
    } catch (_) {
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
    let i = 0;
    function proxima() {
      if (i >= partes.length) {
        if (typeof onEnd === 'function') onEnd();
        return;
      }
      falar(partes[i++], () => setTimeout(proxima, 320));
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

    if (data.executado) {
      const msg = data.fala || data.msg || 'Pronto! Manejo registrado.';
      resetTudo();
      addMsg(msg, 'bot');
      setHint(msg, 'sucesso');
      falarNatural(msg);
      notificarAtualizacao();
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
      setHint('Toque para responder', 'dialogo');
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
    saudarSeNecessario();
    setHint('Toque no botão laranja e fale seu comando', '');
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
    }
    document.dispatchEvent(new CustomEvent('caderno:apontamento-atualizado'));
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
      const data = await resp.json();

      if (!data.ok) {
        showDigitando(false);
        const errMsg = data.err || 'Erro ao processar áudio.';
        addMsg(errMsg, 'bot');
        setHint(errMsg, 'erro');
        falarNatural(errMsg);
        return;
      }

      tratarResposta(data);
    } catch (err) {
      console.error(err);
      showDigitando(false);
      addMsg('Falha na comunicação com o servidor.', 'bot');
      setHint('Sem conexão — tente de novo', 'erro');
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
    if (gravando) pararGravacao();
    else iniciarGravacao();
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
