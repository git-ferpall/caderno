// ================================
// 📦 Silo de Dados - Caderno de Campo
// ================================
document.addEventListener('DOMContentLoaded', () => {
  atualizarLista();
  atualizarUso();

  // 📤 Upload manual
  document.getElementById('btn-silo-arquivo').addEventListener('click', async () => {
    if (!(await checarLimiteAntesUpload())) return;
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*,application/pdf,text/plain';
    input.onchange = () => enviarArquivo(input.files[0]);
    input.click();
  });

  // 📸 Escanear documento (usar câmera)
  document.getElementById('btn-silo-scan').addEventListener('click', async () => {
    if (!(await checarLimiteAntesUpload())) return;
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.capture = 'environment';
    input.onchange = () => enviarArquivo(input.files[0], 'scan');
    input.click();
  });
});

// ===================================
// 🚀 Upload com barra de progresso
// ===================================
async function enviarArquivo(file, origem = 'upload') {
  if (!file) return;

  // Popup de progresso
  let popup = document.createElement('div');
  popup.className = 'upload-popup';
  popup.innerHTML = `
    <div class="upload-box">
      <h3>Enviando arquivo...</h3>
      <div class="progress-bar-bg">
        <div class="progress-bar-fill"></div>
      </div>
      <span class="progress-text">0%</span>
      <button class="cancel-upload">Cancelar</button>
    </div>`;
  document.body.appendChild(popup);

  const bar = popup.querySelector('.progress-bar-fill');
  const txt = popup.querySelector('.progress-text');
  const cancelBtn = popup.querySelector('.cancel-upload');

  const fd = new FormData();
  fd.append('arquivo', file);
  fd.append('origem', origem);

  const xhr = new XMLHttpRequest();
  xhr.open('POST', '../funcoes/silo/upload_arquivo.php', true);

  // Progresso
  xhr.upload.onprogress = e => {
    if (e.lengthComputable) {
      const percent = Math.round((e.loaded / e.total) * 100);
      bar.style.width = percent + '%';
      txt.textContent = percent + '%';
    }
  };

  // Cancelar
  cancelBtn.onclick = () => {
    xhr.abort();
    popup.remove();
    alert('Upload cancelado.');
  };

  xhr.onload = () => {
    popup.remove();
    try {
      const j = JSON.parse(xhr.responseText);
      if (j.ok) {
        alert('✅ Arquivo enviado com sucesso!');
        atualizarLista();
        atualizarUso();
      } else {
        alert('❌ ' + (j.msg || j.err || 'Falha desconhecida.'));
      }
    } catch {
      console.error(xhr.responseText);
      alert('❌ Retorno inválido do servidor.');
    }
  };

  xhr.onerror = () => {
    popup.remove();
    alert('❌ Erro de conexão.');
  };

  xhr.send(fd);
}

// ===================================
// 📜 Atualiza lista
// ===================================
async function atualizarLista() {
  try {
    const res = await fetch('../funcoes/silo/listar_arquivos.php');
    const j = await res.json();
    const box = document.querySelector('.silo-arquivos');
    box.innerHTML = '';

    if (!j.ok || !Array.isArray(j.arquivos)) {
      console.error('Resposta inválida:', j);
      box.innerHTML = '<p>❌ Erro ao carregar arquivos.</p>';
      return;
    }

    if (j.arquivos.length === 0) {
      box.innerHTML = '<p style="text-align:center; opacity:0.6;">Nenhum arquivo enviado ainda.</p>';
      return;
    }

    j.arquivos.forEach(a => {
      const tipo = a.tipo_arquivo ? a.tipo_arquivo.split('/').pop() : 'file';
      const icon = getIconClass(tipo);
      const url = `../funcoes/silo/download_arquivo.php?id=${a.id}`;

      const div = document.createElement('div');
      div.className = 'silo-item-box';
      div.innerHTML = `
        <div class="silo-item silo-arquivo">
          <div class="btn-icon ${icon}"></div>
          <span class="silo-item-title">${a.nome_arquivo}</span>
        </div>
        <div class="silo-item-actions">
          <button class="icon-download" title="Baixar" onclick="baixarArquivo('${url}')"></button>
          <button class="icon-trash" title="Excluir" onclick="excluirArquivo(${a.id})"></button>
        </div>`;
      box.appendChild(div);
    });
  } catch (err) {
    console.error('Erro ao atualizar lista:', err);
    document.querySelector('.silo-arquivos').innerHTML =
      '<p>❌ Falha ao comunicar com o servidor.</p>';
  }
}

// ===================================
// ⬇️ Baixar arquivo
// ===================================
function baixarArquivo(url) {
  const link = document.createElement('a');
  link.href = url;
  link.target = '_blank';
  link.download = '';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// ===================================
// 🗑️ Excluir arquivo
// ===================================
async function excluirArquivo(id) {
  if (!confirm('Excluir este arquivo?')) return;
  const fd = new FormData();
  fd.append('id', id);
  const res = await fetch('../funcoes/silo/excluir_arquivo.php', { method: 'POST', body: fd });
  const j = await res.json();
  if (j.ok) {
    alert('🗑️ Arquivo removido!');
    atualizarLista();
    atualizarUso();
  } else {
    alert('❌ ' + j.err);
  }
}

// ===================================
// 📊 Atualiza uso
// ===================================
async function atualizarUso() {
  const res = await fetch('../funcoes/silo/get_uso.php');
  const j = await res.json();
  if (j.ok) {
    const usado = parseFloat(j.usado).toFixed(3);
    const limite = parseFloat(j.limite).toFixed(2);
    document.querySelector('.silo-info-title').innerText =
      `${j.percent}% utilizado (${usado} GB de ${limite} GB)`;
    document.querySelector('.silo-info-bar').style.background =
      `linear-gradient(to right, var(--verde) ${j.percent}%, transparent ${j.percent}%)`;
  }
}

// ===================================
// 🚫 Checa limite antes do upload
// ===================================
async function checarLimiteAntesUpload() {
  const res = await fetch('../funcoes/silo/get_uso.php');
  const j = await res.json();
  if (j.ok && j.usado >= j.limite) {
    alert(`❌ Limite de ${j.limite} GB atingido. Exclua arquivos antes de enviar novos.`);
    return false;
  }
  return true;
}

// ===================================
// 🧩 Define ícone
// ===================================
function getIconClass(tipo) {
  tipo = tipo.toLowerCase();
  if (tipo.includes('pdf')) return 'icon-pdf';
  if (tipo.includes('txt')) return 'icon-txt';
  if (tipo.includes('image') || tipo === 'jpg' || tipo === 'jpeg' || tipo === 'png')
    return 'icon-img';
  return 'icon-file';
}
