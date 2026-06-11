(function () {
  'use strict';

  const API_PROCESSAR = '../funcoes/ia/processar_audio.php';
  const API_EXECUTAR = '../funcoes/ia/executar_intent.php';

  let mediaRecorder = null;
  let audioStream = null;
  let chunks = [];
  let gravando = false;
  let intentPendente = null;
  let micPronto = false;

  const fab = document.getElementById('assistente-voz-btn');
  const panel = document.getElementById('assistente-voz-panel');
  const btnFechar = document.getElementById('assistente-voz-fechar');
  const btnGravar = document.getElementById('assistente-voz-gravar');
  const btnConfirmar = document.getElementById('assistente-voz-confirmar');
  const btnCancelar = document.getElementById('assistente-voz-cancelar');
  const elStatus = document.getElementById('assistente-voz-status');
  const elTranscricao = document.getElementById('assistente-voz-transcricao');
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
    elTranscricao.classList.add('d-none');
    elTranscricao.textContent = '';
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
    resetConfirmacao();
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
    if (gravando) return;

    resetConfirmacao();

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
      setStatus('Gravando… fale agora.');
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

    try {
      const resp = await fetch(API_PROCESSAR, { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await resp.json();

      if (!data.ok) {
        setStatus(data.err || 'Erro ao processar áudio.', 'erro');
        return;
      }

      if (data.transcricao) {
        elTranscricao.textContent = '“' + data.transcricao + '”';
        elTranscricao.classList.remove('d-none');
      }

      if (data.executado) {
        setStatus(data.msg || 'Pronto!', 'sucesso');
        notificarAtualizacao();
        return;
      }

      if (data.precisa_confirmacao && data.intent) {
        intentPendente = data.intent;
        elResumo.textContent = data.resumo || data.msg || 'Confirmar ação?';
        elConfirmacao.classList.remove('d-none');
        setStatus('Confirme se está correto.', 'confirmacao');
        return;
      }

      setStatus(data.msg || data.intent?.mensagem || 'Não entendi. Tente reformular.', 'erro');
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

    try {
      const resp = await fetch(API_EXECUTAR, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ intent: intentPendente }),
      });
      const data = await resp.json();

      if (data.ok && data.executado) {
        setStatus(data.msg || 'Pronto!', 'sucesso');
        resetConfirmacao();
        notificarAtualizacao();
      } else {
        setStatus(data.msg || data.err || 'Não foi possível executar.', 'erro');
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
      // Solicita microfone no mesmo clique — exige gesto do usuário para o prompt aparecer
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
    resetConfirmacao();
    setStatus(micPronto ? 'Comando cancelado. Grave novamente.' : 'Toque em Gravar após permitir o microfone.');
  });

  window.addEventListener('pagehide', liberarStream);
})();
