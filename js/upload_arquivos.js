// ==========================================
// 📤 Upload múltiplo com progresso + cancelar
// ==========================================

let uploadEmAndamento = [];

/**
 * Envia arquivos para o Silo de Dados
 * @param {FileList} arquivos
 * @param {string|number} parent_id - ID da pasta atual
 */
async function enviarArquivosSilo(arquivos, parent_id = '') {
  if (!arquivos || arquivos.length === 0) return;

  const popup = document.getElementById('uploadPopup');
  const lista = document.getElementById('uploadLista');
  const resumo = document.getElementById('uploadResumo');
  let btnCancelar = document.getElementById('cancelarUpload');

  if (!btnCancelar) {
    btnCancelar = document.createElement('button');
    btnCancelar.id = 'cancelarUpload';
    btnCancelar.textContent = '🛑 Cancelar Upload';
    btnCancelar.style.cssText =
      'background:#c62828;color:#fff;border:none;padding:6px 12px;border-radius:6px;margin-top:12px;cursor:pointer;';
    document.querySelector('.upload-box').appendChild(btnCancelar);
  }

  lista.innerHTML = '';
  popup.style.display = 'flex';
  resumo.textContent = `Enviando ${arquivos.length} arquivo(s)...`;

  // Botão cancelar
  btnCancelar.style.display = 'block';
  btnCancelar.onclick = () => {
    uploadEmAndamento.forEach((x) => x.abort());
    uploadEmAndamento = [];
    resumo.textContent = '🛑 Upload cancelado pelo usuário.';
    setTimeout(() => (popup.style.display = 'none'), 1000);
  };

  let concluidos = 0;

  for (const arquivo of arquivos) {
    const item = document.createElement('div');
    item.className = 'upload-item';
    item.innerHTML = `
      <div class="nome">${arquivo.name}</div>
      <div class="progress-bar"><div class="progress-fill"></div></div>
      <div class="progress-text">0%</div>
    `;
    lista.appendChild(item);

    const barra = item.querySelector('.progress-fill');
    const texto = item.querySelector('.progress-text');

    await new Promise((resolve) => {
      const fd = new FormData();
      fd.append('arquivo', arquivo);
      fd.append('parent_id', parent_id || '');

      const xhr = new XMLHttpRequest();
      xhr.open('POST', '../funcoes/silo/upload_arquivo.php', true);
      uploadEmAndamento.push(xhr);

      // Progresso individual
      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
          const percent = Math.round((e.loaded / e.total) * 100);
          barra.style.width = percent + '%';
          texto.textContent = percent + '%';
        }
      });

      // Retorno servidor
      xhr.onreadystatechange = async () => {
        if (xhr.readyState === XMLHttpRequest.DONE) {
          uploadEmAndamento = uploadEmAndamento.filter((x) => x !== xhr);
          concluidos++;

          try {
            const j = JSON.parse(xhr.responseText);
            if (j.ok) {
              barra.style.width = '100%';
              barra.style.background = '#2e7d32';
              texto.textContent = '✅ Concluído';
            } else {
              barra.style.background = '#c62828';
              texto.textContent = '❌ ' + (j.err || 'Erro');
            }
          } catch {
            barra.style.background = '#c62828';
            texto.textContent = '⚠️ Erro inesperado';
          }

          resumo.textContent = `(${concluidos}/${arquivos.length}) concluído(s)`;
          resolve();
        }
      };

      xhr.onerror = () => {
        barra.style.background = '#c62828';
        texto.textContent = '❌ Falha';
        concluidos++;
        resumo.textContent = `(${concluidos}/${arquivos.length}) concluído(s)`;
        resolve();
      };

      xhr.send(fd);
    });
  }

  resumo.textContent = '✅ Uploads finalizados.';
  btnCancelar.style.display = 'none';
  setTimeout(() => (popup.style.display = 'none'), 1500);
  if (typeof atualizarLista === 'function') atualizarLista();
}

window.enviarArquivosSilo = enviarArquivosSilo;
