// ==========================================
// 📤 Upload múltiplo com barra de progresso
// ==========================================

async function enviarArquivosSilo(arquivos, parent_id = '') {
  if (!arquivos || arquivos.length === 0) return;

  const popup = document.getElementById('uploadPopup');
  const lista = document.getElementById('uploadLista');
  const resumo = document.getElementById('uploadResumo');

  lista.innerHTML = '';
  popup.style.display = 'flex';
  resumo.textContent = `Enviando ${arquivos.length} arquivo(s)...`;

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

      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
          const percent = Math.round((e.loaded / e.total) * 100);
          barra.style.width = percent + '%';
          texto.textContent = percent + '%';
        }
      });

      xhr.onreadystatechange = async () => {
        if (xhr.readyState === XMLHttpRequest.DONE) {
          try {
            const j = JSON.parse(xhr.responseText);
            if (j.ok) {
              barra.style.width = '100%';
              texto.textContent = '✅ Concluído';
            } else {
              barra.style.background = '#c62828';
              texto.textContent = '❌ Erro';
            }
          } catch {
            barra.style.background = '#c62828';
            texto.textContent = '⚠️ Erro';
          }

          concluidos++;
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

  // ✅ Tudo finalizado
  resumo.textContent = '✅ Todos os uploads finalizados';
  setTimeout(() => {
    popup.style.display = 'none';
  }, 1200);

  if (typeof atualizarLista === 'function') await atualizarLista();
}
window.enviarArquivosSilo = enviarArquivosSilo;
