(function () {
  'use strict';

  const API_PROCESSAR = '/funcoes/ia/processar_audio.php';
  const API_EXECUTAR = '/funcoes/ia/executar_intent.php';

  const SAUDACAO =
    'Olá! Sou o assistente Frutag. Fale o manejo que quer registrar — por exemplo: adicionar plantio, colheita ou irrigação.';

  let mediaRecorder = null;
  let audioStream = null;
  let chunks = [];
  let gravando = false;
  let intentPendente = null;
  let intentParcial = null;
  let campoDialogo = null;
  let micPronto = false;
  let falando = false;
  let panelAberto = false;
  let saudacaoFeita = false;

  const fab = document.getElementById('assistente-voz-btn');
  const panel = document.getElementById('assistente-voz-panel');
  const btnFechar = document.getElementById('assistente-voz-fechar');
  const btnGravar = document.getElementById('assistente-voz-gravar');
  const btnConfirmar = document.getElementById('assistente-voz-confirmar');
  const btnCancelar = document.getElementById('assistente-voz-cancelar');
  const elStatus = document.getElementById('assistente-voz-status');
  const elChat = document.getElementById('assistente-voz-chat');
  const elDigitando = document.getElementById('assistente-voz-digitando');
  const elConfirmacao = document.getElementById('assistente-voz-confirmacao');
  const elResumo = document.getElementById('assistente-voz-resumo');
  const elGravarTexto = document.getElementById('assistente-voz-gravar-texto');
  const elAvatar = document.getElementById('assistente-voz-avatar');
  const elProgresso = document.getElementById('assistente-voz-progresso');
  const elProgressoFill = document.getElementById('assistente-voz-progresso-fill');
  const elProgressoLabel = document.getElementById('assistente-voz-progresso-label');

  if (!fab || !panel) return;

  function setStatus(msg, tipo) {
    if (elStatus) {
      elStatus.textContent = msg;
      elStatus.className = 'assistente-voz-status assistente-voz-status--sr' + (tipo ? ' assistente-voz-status--' + tipo : '');
    }
  }

  function scrollChat() {
    if (elChat) {
      elChat.scrollTop = elChat.scrollHeight;
    }
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

  function setAvatarFalando(ativo) {
    elAvatar?.classList.toggle('assistente-voz-avatar--falando', !!ativo);
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
    u.rate = 0.92;
    u.pitch = 1;

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
    window.speechSynthesis.speak(u);
  }

  if ('speechSynthesis' in window) {
    window.speechSynthesis.getVoices();
    window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
  }

  function saudarSeNecessario() {
    if (saudacaoFeita) return;
    saudacaoFeita = true;
    addMsg(SAUDACAO, 'bot');
  }

  function autoGravarResposta() {
    if (micPronto && !gravando && !falando) {
      setTimeout(() => {
        if ((intentParcial || intentPendente === null) && !gravando && !falando) {
          addMsg('Pode falar…', 'sistema');
          iniciarGravacao();
        }
      }, 500);
    }
  }

  function tratarResposta(data) {
    showDigitando(false);

    if (data.transcricao) {
      addMsg(data.transcricao, 'user');
    }

    if (data.executado) {
      const msg = data.msg || 'Pronto! Manejo registrado.';
      resetTudo();
      addMsg(msg, 'bot');
      setStatus(msg, 'sucesso');
      falar(msg);
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
      setStatus('Aguardando sua resposta…', 'dialogo');

      falar(fala, autoGravarResposta);
      return;
    }

    if (data.precisa_confirmacao && data.intent) {
      resetDialogo();
      intentPendente = data.intent;
      const resumo = data.resumo || data.msg || 'Confirmar ação?';
      elResumo.textContent = resumo;
      elConfirmacao?.classList.remove('d-none');
      updateProgresso(0, 0);

      const fala = data.fala || 'Perfeito! Resumo: ' + resumo + ' Posso confirmar e salvar?';
      addMsg(fala, 'bot');
      setStatus('Confirme ou grave de novo.', 'confirmacao');
      falar(fala);
      return;
    }

    resetDialogo();
    const errMsg = data.msg || data.intent?.mensagem || 'Não entendi. Tente reformular.';
    addMsg(errMsg, 'bot');
    setStatus(errMsg, 'erro');
    falar(errMsg);
  }

  function mensagemMicrofoneBloqueado() {
    return 'Microfone bloqueado. Clique no cadeado ao lado da URL, permita o microfone e recarregue a página.';
  }

  function getMicStream() {
    if (!window.isSecureContext) {
      return Promise.reject(Object.assign(new Error('SECURE_CONTEXT'), { code: 'SECURE_CONTEXT' }));
    }

    const modern = navigator.mediaDevices && navigator.mediaDevices.getUserMedia;
    if (modern) {
      return navigator.mediaDevices.getUserMedia({
        audio: { echoCancellation: true, noiseSuppression: true },
      });
    }

    const legacy =
      navigator.getUserMedia ||
      navigator.webkitGetUserMedia ||
      navigator.mozGetUserMedia ||
      navigator.msGetUserMedia;

    if (!legacy) {
      return Promise.reject(Object.assign(new Error('UNSUPPORTED'), { code: 'UNSUPPORTED' }));
    }

    return new Promise((resolve, reject) => {
      legacy.call(navigator, { audio: true }, resolve, reject);
    });
  }

  async function consultarPermissaoMicrofone() {
    if (!navigator.permissions || !navigator.permissions.query) return null;
    try {
      return await navigator.permissions.query({ name: 'microphone' });
    } catch (_) {
      return null;
    }
  }

  function tratarErroMicrofone(err) {
    console.error('[assistente-voz]', err);
    const nome = err && (err.name || err.code || '');

    if (nome === 'SECURE_CONTEXT') {
      addMsg('O microfone só funciona em HTTPS.', 'bot');
      setStatus('HTTPS necessário.', 'erro');
      return;
    }
    if (nome === 'UNSUPPORTED') {
      addMsg('Navegador sem suporte a gravação. Use Chrome ou Edge.', 'bot');
      return;
    }
    if (nome === 'NotFoundError' || nome === 'DevicesNotFoundError') {
      addMsg('Nenhum microfone encontrado.', 'bot');
      return;
    }
    if (nome === 'NotAllowedError' || nome === 'PermissionDeniedError' || nome === 'SecurityError') {
      addMsg(mensagemMicrofoneBloqueado(), 'bot');
      setStatus('Microfone bloqueado.', 'erro');
      return;
    }
    addMsg('Não foi possível acessar o microfone.', 'bot');
  }

  function liberarStream() {
    if (audioStream) {
      audioStream.getTracks().forEach((t) => t.stop());
      audioStream = null;
    }
    micPronto = false;
  }

  async function garantirPermissaoMicrofone() {
    if (audioStream && micPronto) return audioStream;

    const perm = await consultarPermissaoMicrofone();
    if (perm && perm.state === 'denied') {
      addMsg(mensagemMicrofoneBloqueado(), 'bot');
      throw new Error('DENIED');
    }

    try {
      audioStream = await getMicStream();
      micPronto = true;

      if (perm) {
        perm.onchange = () => {
          if (perm.state === 'denied') {
            liberarStream();
            addMsg(mensagemMicrofoneBloqueado(), 'bot');
          }
        };
      }

      setStatus('Microfone pronto.', '');
      return audioStream;
    } catch (err) {
      micPronto = false;
      tratarErroMicrofone(err);
      throw err;
    }
  }

  function abrirPanel() {
    panel.classList.remove('d-none');
    fab.setAttribute('aria-expanded', 'true');
    panelAberto = true;
    saudarSeNecessario();
  }

  function fecharPanel() {
    panel.classList.add('d-none');
    fab.setAttribute('aria-expanded', 'false');
    panelAberto = false;
    pararGravacao();
    pararFala();
    resetTudo();
    resetChat();
    showDigitando(false);
    setStatus('Assistente fechado.', '');
  }

  function notificarAtualizacao() {
    if (typeof window.carregarManejos === 'function') {
      window.carregarManejos();
    }
    document.dispatchEvent(new CustomEvent('caderno:apontamento-atualizado'));
  }

  function escolherMime() {
    const tipos = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4', 'audio/aac'];
    for (const t of tipos) {
      if (typeof MediaRecorder !== 'undefined' && MediaRecorder.isTypeSupported(t)) return t;
    }
    return '';
  }

  async function iniciarGravacao() {
    if (gravando || falando) return;

    if (!intentParcial && !intentPendente) {
      resetConfirmacao();
    }

    if (typeof MediaRecorder === 'undefined') {
      addMsg('Seu navegador não suporta gravação.', 'bot');
      return;
    }

    try {
      const stream = await garantirPermissaoMicrofone();
      const mime = escolherMime();
      mediaRecorder = mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream);

      chunks = [];
      mediaRecorder.ondataavailable = (e) => {
        if (e.data && e.data.size > 0) chunks.push(e.data);
      };
      mediaRecorder.onstop = () => enviarAudio(mime || mediaRecorder.mimeType || 'audio/webm');

      mediaRecorder.start();
      gravando = true;
      btnGravar?.classList.add('assistente-voz-gravar--ativo');
      btnGravar?.setAttribute('aria-pressed', 'true');
      if (elGravarTexto) elGravarTexto.textContent = 'Parar';
      setStatus(intentParcial ? 'Gravando resposta…' : 'Gravando…', 'dialogo');
    } catch (_) {
      /* mensagem já exibida */
    }
  }

  function pararGravacao() {
    if (!gravando || !mediaRecorder) return;

    gravando = false;
    btnGravar?.classList.remove('assistente-voz-gravar--ativo');
    btnGravar?.setAttribute('aria-pressed', 'false');
    if (elGravarTexto) elGravarTexto.textContent = 'Gravar';

    try {
      if (mediaRecorder.state !== 'inactive') mediaRecorder.stop();
    } catch (_) { /* ignore */ }
  }

  async function enviarAudio(mime) {
    if (!chunks.length) {
      addMsg('Não captei áudio. Tente falar um pouco mais perto.', 'bot');
      return;
    }

    showDigitando(true);
    setStatus('Processando…', 'processando');
    if (btnGravar) btnGravar.disabled = true;

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
        setStatus(errMsg, 'erro');
        falar(errMsg);
        return;
      }

      tratarResposta(data);
    } catch (err) {
      console.error(err);
      showDigitando(false);
      addMsg('Falha na comunicação com o servidor.', 'bot');
      setStatus('Erro de rede.', 'erro');
    } finally {
      if (btnGravar) btnGravar.disabled = false;
    }
  }

  async function confirmarIntent() {
    if (!intentPendente) return;

    showDigitando(true);
    setStatus('Salvando…', 'processando');
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
        setStatus(msg, 'sucesso');
        falar(msg);
        notificarAtualizacao();
      } else {
        const errMsg = data.msg || data.err || 'Não foi possível executar.';
        addMsg(errMsg, 'bot');
        setStatus(errMsg, 'erro');
        falar(errMsg);
      }
    } catch (err) {
      console.error(err);
      showDigitando(false);
      addMsg('Falha ao salvar.', 'bot');
    } finally {
      btnConfirmar.disabled = false;
    }
  }

  fab.addEventListener('click', () => {
    if (panel.classList.contains('d-none')) {
      abrirPanel();
      garantirPermissaoMicrofone().catch(() => {});
    } else {
      fecharPanel();
    }
  });

  btnFechar?.addEventListener('click', fecharPanel);

  btnGravar?.addEventListener('click', () => {
    if (gravando) pararGravacao();
    else iniciarGravacao();
  });

  btnConfirmar?.addEventListener('click', confirmarIntent);

  btnCancelar?.addEventListener('click', () => {
    pararFala();
    resetTudo();
    elConfirmacao?.classList.add('d-none');
    addMsg('Ok, vamos recomeçar. Fale o manejo de novo.', 'bot');
    falar('Ok, vamos recomeçar. Fale o manejo de novo.');
  });

  window.addEventListener('pagehide', () => {
    liberarStream();
    pararFala();
  });
})();
