(function () {
  'use strict';

  const API_PROCESSAR = '../funcoes/ia/processar_audio.php';
  const API_EXECUTAR = '../funcoes/ia/executar_intent.php';

  let mediaRecorder = null;
  let chunks = [];
  let gravando = false;
  let intentPendente = null;

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
    ];
    for (const t of tipos) {
      if (MediaRecorder.isTypeSupported(t)) return t;
    }
    return '';
  }

  async function iniciarGravacao() {
    if (gravando) return;

    resetConfirmacao();

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      setStatus('Seu navegador não suporta gravação de áudio.', 'erro');
      return;
    }

    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const mime = escolherMime();
      mediaRecorder = mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream);

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
    } catch (err) {
      console.error(err);
      setStatus('Permita o acesso ao microfone para usar o assistente.', 'erro');
    }
  }

  function pararGravacao() {
    if (!gravando || !mediaRecorder) return;

    gravando = false;
    btnGravar.classList.remove('assistente-voz-gravar--ativo');
    btnGravar.setAttribute('aria-pressed', 'false');
    elGravarTexto.textContent = 'Gravar';

    try {
      mediaRecorder.stop();
      mediaRecorder.stream.getTracks().forEach((t) => t.stop());
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
    setStatus('Comando cancelado. Grave novamente.');
  });
})();
