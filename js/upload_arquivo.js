async function enviarArquivoSilo(arquivo, parent_id = '') {
  return new Promise((resolve, reject) => {
    const popup = document.getElementById('uploadPopup');
    const barra = document.getElementById('uploadProgress');
    const texto = document.getElementById('uploadPercent');

    popup.style.display = 'flex';
    barra.style.width = '0%';
    texto.textContent = '0%';

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
        popup.style.display = 'none';

        try {
          const j = JSON.parse(xhr.responseText);
          if (j.ok) {
            abrirPopup('✅ Sucesso', j.msg || 'Upload concluído!');
            if (typeof atualizarLista === 'function') await atualizarLista();
            resolve(j);
          } else {
            abrirPopup('❌ Erro', j.err || 'Falha no upload.');
            reject(j);
          }
        } catch {
          abrirPopup('⚠️ Erro', 'Resposta inválida do servidor.');
          reject();
        }
      }
    };

    xhr.onerror = () => {
      popup.style.display = 'none';
      abrirPopup('❌ Erro', 'Falha de conexão durante o upload.');
      reject();
    };

    xhr.send(fd);
  });
}
