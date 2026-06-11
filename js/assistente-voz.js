(function () {
  'use strict';

  const API_PROCESSAR = '/funcoes/ia/processar_audio.php';
  const API_EXECUTAR = '/funcoes/ia/executar_intent.php';

  let mediaRecorder = null;
  let audioStream = null;
  let chunks = [];
  let gravando = false;
  let intentPendente = null;
  let intentParcial = null;
  let campoDialogo = null;
  let micPronto = false;
  let falando = false;

  const fab = document.getElementById('assistente-voz-btn');
  const panel = document.getElementById('assistente-voz-panel');
  const btnFechar = document.getElementById('assistente-voz-fechar');
  const btnGravar = document.getElementById('assistente-voz-gravar');
  const btnConfirmar = document.getElementById('assistente-voz-confirmar');
  const btnCancelar = document.getElementById('assistente-voz-cancelar');
  const elStatus = document.getElementById('assistente-voz-status');
  const elTranscricao = document.getElementById('assistente-voz-transcricao');
  const elDialogo = document.getElementById('assistente-voz-dialogo');
  const elPergunta = document.getElementById('assistente-voz-pergunta');
  const elConfirmacao = document.getElementById('assistente-voz-confirmacao');
  const elResumo = document.getElementById('assistente-voz-resumo');
  const elGravarTexto = document.getElementById('assistente-voz-gravar-texto');

  if (!fab || !panel) return;

  function setStatus(msg, tipo) {
    elStatus.textContent = msg;
    elStatus.className = 'assistente-voz-status' + (tipo ? ' assistente-voz-status--' + tipo : '');
  }

  function resetConfirmacao() {
    intentPendente = null;
    elConfirmacao.classList.add('d-none');
  }

  function resetDialogo() {
    intentParcial = null;
    campoDialogo = null;
    elDialogo?.classList.add('d-none');
    if (elPergunta) elPergunta.textContent = '';
  }

  function resetTranscricao() {
    elTranscricao.classList.add('d-none');
    elTranscricao.textContent = '';
  }

  function resetTudo() {
    resetConfirmacao();
    resetDialogo();
    resetTranscricao();
  }

  function pararFala() {
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
    }
    falando = false;
  }

  /** Fala a resposta usando voz do sistema (pt-BR). */
  function falar(texto, onEnd) {
    if (!texto || !('speechSynthesis' in window)) {
      if (typeof onEnd === 'function') onEnd();
      return;
    }

    pararFala();
    const u = new SpeechSynthesisUtterance(texto);
    u.lang = 'pt-BR';
    u.rate = 0.95;
    u.pitch = 1;

    const voices = window.speechSynthesis.getVoices();
    const pt = voices.find((v) => v.lang.startsWith('pt'));
    if (pt) u.voice = pt;

    u.onend = () => {
      falando = false;
      if (typeof onEnd === 'function') onEnd();
    };
    u.onerror = () => {
      falando = false;
      if (typeof onEnd === 'function') onEnd();
    };

    falando = true;
    window.speechSynthesis.speak(u);
  }

  if ('speechSynthesis' in window) {
    window.speechSynthesis.getVoices();
    window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
  }

  function mostrarDialogo(pergunta) {
    resetConfirmacao();
    if (elPergunta) elPergunta.textContent = pergunta;
    elDialogo?.classList.remove('d-none');
    setStatus('Aguardando sua resposta…', 'dialogo');
  }

  function tratarResposta(data) {
    if (data.transcricao) {
      elTranscricao.textContent = '“' + data.transcricao + '”';
      elTranscricao.classList.remove('d-none');
    }

    if (data.executado) {
      const msg = data.msg || 'Pronto!';
      resetTudo();
      setStatus(msg, 'sucesso');
      falar(msg);
      notificarAtualizacao();
      return;
    }

    if (data.precisa_dialogo && data.intent_parcial) {
      intentParcial = data.intent_parcial;
      campoDialogo = data.campo_dialogo || null;
      const pergunta = data.pergunta || data.msg || 'Preciso de mais uma informação.';
      mostrarDialogo(pergunta);
      falar(pergunta, () => {
        if (micPronto && !gravando) {
          setStatus('Pode falar — gravando em instantes…', 'dialogo');
          setTimeout(() => {
            if (intentParcial && !gravando) iniciarGravacao();
          }, 600);
        }
      });
      return;
    }

    if (data.precisa_confirmacao && data.intent) {
      resetDialogo();
      intentPendente = data.intent;
      elResumo.textContent = data.resumo || data.msg || 'Confirmar ação?';
      elConfirmacao.classList.remove('d-none');
      const confMsg = 'Confirme se está correto, ou ajuste gravando de novo.';
      setStatus(confMsg, 'confirmacao');
      falar('Entendi. ' + (data.resumo || '') + ' Está correto? Toque em confirmar ou grave de novo.');
      return;
    }

    resetDialogo();
    const errMsg = data.msg || data.intent?.mensagem || 'Não entendi. Tente reformular.';
    setStatus(errMsg, 'erro');
    falar(errMsg);
  }

  function mensagemMicrofoneBloqueado() {
    return 'Microfone bloqueado. Clique no cadeado ou ícone ao lado da URL do site, permita o microfone e recarregue a página.';
  }

  function getMicStream() {
    if (!window.isSecureContext) {
      return Promise.reject(Object.assign(new Error('SECURE_CONTEXT'), { code: 'SECURE_CONTEXT' }));
    }

    const modern = navigator.mediaDevices && navigator.mediaDevices.getUserMedia;
    if (modern) {
      return navigator.mediaDevices.getUserMedia({
        audio: {
          echoCancellation: true,
          noiseSuppression: true,
        },
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
    if (!navigator.permissions || !navigator.permissions.query) {
      return null;
    }
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
      setStatus('O microfone só funciona em HTTPS. Acesse o site com conexão segura.', 'erro');
      return;
    }

    if (nome === 'UNSUPPORTED') {
      setStatus('Seu navegador não suporta gravação de áudio. Tente Chrome, Edge ou Safari atualizado.', 'erro');
      return;
    }

    if (nome === 'NotFoundError' || nome === 'DevicesNotFoundError') {
      setStatus('Nenhum microfone encontrado neste dispositivo.', 'erro');
      return;
    }

    if (nome === 'NotReadableError' || nome === 'TrackStartError') {
      setStatus('Microfone em uso por outro aplicativo. Feche outros apps e tente de novo.', 'erro');
      return;
    }

    if (
      nome === 'NotAllowedError' ||
      nome === 'PermissionDeniedError' ||
      nome === 'SecurityError'
    ) {
      setStatus(mensagemMicrofoneBloqueado(), 'erro');
      return;
    }

    setStatus('Não foi possível acessar o microfone. Verifique as permissões do navegador.', 'erro');
  }

  function liberarStream() {
    if (audioStream) {
      audioStream.getTracks().forEach((t) => t.stop());
      audioStream = null;
    }
    micPronto = false;
  }

  async function garantirPermissaoMicrofone() {
    if (audioStream && micPronto) {
      return audioStream;
    }

    const perm = await consultarPermissaoMicrofone();
    if (perm && perm.state === 'denied') {
      setStatus(mensagemMicrofoneBloqueado(), 'erro');
      throw new Error('DENIED');
    }

    setStatus('Aguardando permissão do microfone…', 'processando');

    try {
      audioStream = await getMicStream();
      micPronto = true;

      if (perm) {
        perm.onchange = () => {
          if (perm.state === 'denied') {
            liberarStream();
            setStatus(mensagemMicrofoneBloqueado(), 'erro');
          }
        };
      }

      setStatus('Microfone liberado. Toque em Gravar e fale seu comando.');
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
  }

  function fecharPanel() {
    panel.classList.add('d-none');
    fab.setAttribute('aria-expanded', 'false');
    pararGravacao();
    pararFala();
    resetTudo();
    setStatus('Toque no microfone e fale seu comando.');
  }

  function notificarAtualizacao() {
    if (typeof window.carregarManejos === 'function') {
      window.carregarManejos();
    }
    document.dispatchEvent(new CustomEvent('caderno:apontamento-atualizado'));
  }

  function escolherMime() {
    const tipos = [
      'audio/webm;codecs=opus',
      'audio/webm',
      'audio/ogg;codecs=opus',
      'audio/mp4',
      'audio/aac',
    ];
    for (const t of tipos) {
      if (typeof MediaRecorder !== 'undefined' && MediaRecorder.isTypeSupported(t)) {
        return t;
      }
    }
    return '';
  }

  async function iniciarGravacao() {
    if (gravando || falando) return;

    if (!intentParcial) {
      resetConfirmacao();
      resetTranscricao();
    }

    if (typeof MediaRecorder === 'undefined') {
      setStatus('Seu navegador não suporta gravação de áudio.', 'erro');
      return;
    }

    try {
      const stream = await garantirPermissaoMicrofone();
      const mime = escolherMime();
      mediaRecorder = mime
        ? new MediaRecorder(stream, { mimeType: mime })
        : new MediaRecorder(stream);

      chunks = [];
      mediaRecorder.ondataavailable = (e) => {
        if (e.data && e.data.size > 0) chunks.push(e.data);
      };
      mediaRecorder.onstop = () => enviarAudio(mime || mediaRecorder.mimeType || 'audio/webm');

      mediaRecorder.start();
      gravando = true;
      btnGravar.classList.add('assistente-voz-gravar--ativo');
      btnGravar.setAttribute('aria-pressed', 'true');
      elGravarTexto.textContent = 'Parar';
      setStatus(intentParcial ? 'Gravando sua resposta…' : 'Gravando… fale agora.');
    } catch (_) {
      /* mensagem já definida em tratarErroMicrofone */
    }
  }

  function pararGravacao() {
    if (!gravando || !mediaRecorder) return;

    gravando = false;
    btnGravar.classList.remove('assistente-voz-gravar--ativo');
    btnGravar.setAttribute('aria-pressed', 'false');
    elGravarTexto.textContent = 'Gravar';

    try {
      if (mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
      }
    } catch (_) { /* ignore */ }
  }

  async function enviarAudio(mime) {
    if (!chunks.length) {
      setStatus('Nenhum áudio capturado. Tente novamente.', 'erro');
      return;
    }

    setStatus('Processando áudio…', 'processando');
    btnGravar.disabled = true;

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
        const errMsg = data.err || 'Erro ao processar áudio.';
        setStatus(errMsg, 'erro');
        falar(errMsg);
        return;
      }

      tratarResposta(data);
    } catch (err) {
      console.error(err);
      setStatus('Falha na comunicação com o servidor.', 'erro');
    } finally {
      btnGravar.disabled = false;
    }
  }

  async function confirmarIntent() {
    if (!intentPendente) return;

    setStatus('Executando…', 'processando');
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

      if (data.ok && data.executado) {
        const msg = data.msg || 'Pronto!';
        resetTudo();
        setStatus(msg, 'sucesso');
        falar(msg);
        notificarAtualizacao();
      } else {
        const errMsg = data.msg || data.err || 'Não foi possível executar.';
        setStatus(errMsg, 'erro');
        falar(errMsg);
      }
    } catch (err) {
      console.error(err);
      setStatus('Falha ao executar comando.', 'erro');
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
    setStatus(micPronto ? 'Grave de novo e fale o comando.' : 'Toque em Gravar após permitir o microfone.');
  });

  window.addEventListener('pagehide', () => {
    liberarStream();
    pararFala();
  });
})();
