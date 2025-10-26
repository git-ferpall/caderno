// ================================
// 📦 Silo de Dados - Caderno de Campo
// ================================
document.addEventListener('DOMContentLoaded', () => {
  atualizarLista();
  atualizarUso();

  // 📤 Upload de arquivo manual
  document.getElementById('btn-silo-arquivo').addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*,application/pdf,text/plain';
    input.onchange = () => enviarArquivo(input.files[0]);
    input.click();
  });

  // 📸 Escanear documento (abrir câmera)
  document.getElementById('btn-silo-scan').addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.capture = 'environment';
    input.onchange = () => enviarArquivo(input.files[0], 'scan');
    input.click();
  });
});

// ===================================
// 🚀 Função de upload com barra de progresso
// ===================================
async function enviarArquivo(file, origem = 'upload') {
  if (!file) return;

  // 🧱 Cria popup de progresso
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
    </div>
  `;
  document.body.appendChild(popup);

  const bar = popup.querySelector('.progress-bar-fill');
  const txt = popup.querySelector('.progress-text');
  const cancelBtn = popup.querySelector('.cancel-upload');

  const fd = new FormData();
  fd.append('arquivo', file);
  fd.append('origem', origem);

  const xhr = new XMLHttpRequest();
  xhr.open('POST', '../funcoes/silo/upload_arquivo.php', true);

  // 🔄 Progresso em tempo real
  xhr.upload.onprogress = function (e) {
    if (e.lengthComputable) {
      const percent = Math.round((e.loaded / e.total) * 100);
      bar.style.width = percent + '%';
      txt.textContent = percent + '%';
    }
  };

  // ❌ Cancelar upload
  cancelBtn.addEventListener('click', () => {
    xhr.abort();
    popup.remove();
    alert('Upload cancelado pelo usuário.');
  });

  // ✅ Conclusão
  xhr.onload = function () {
    popup.remove();
    try {
      const j = JSON.parse(xhr.responseText);
      if (j.ok) {
        alert('✅ Arquivo enviado com sucesso!');
        atualizarLista();
        atualizarUso();
      } else {
        alert('❌ Erro: ' + (j.err || 'Falha desconhecida.'));
      }
    } catch (e) {
      console.error('Erro JSON:', xhr.responseText);
      alert('❌ Falha: retorno inválido do servidor.');
    }
  };

  // ⚠️ Erro de conexão
  xhr.onerror = function () {
    popup.remove();
    alert('❌ Erro na conexão durante o upload.');
  };

  xhr.send(fd);
}

// ===================================
// 📜 Atualiza lista de arquivos
// ===================================
async function atualizarLista() {
  try {
    const res = await fetch('../funcoes/silo/listar_arquivos.php');
    const j = await res.json();

    const box = document.querySelector('.silo-arquivos');
    box.innerHTML = '';

    // ✅ Validação de resposta
    if (!j.ok || !Array.isArray(j.arquivos)) {
      console.error('Resposta inválida:', j);
      box.innerHTML = '<p>❌ Erro ao carregar arquivos.</p>';
      return;
    }

    const arquivos = j.arquivos;

    if (arquivos.length === 0) {
      box.innerHTML = '<p style="text-align:center; opacity:0.6;">Nenhum arquivo enviado ainda.</p>';
      return;
    }

    arquivos.forEach(a => {
      const div = document.createElement('div');
      div.className = 'silo-item-box';

      // Extrai tipo de arquivo (ex: "application/pdf" → "pdf")
      const tipo = a.tipo_arquivo ? a.tipo_arquivo.split('/').pop() : 'file';
      const icon = getIconClass(tipo);

      // Caminho do arquivo (ajuste conforme estrutura de uploads)
      const urlArquivo = `/uploads/silo/${a.nome_arquivo}`;

      div.innerHTML = `
        <div class="silo-item silo-arquivo">
          <div class="btn-icon ${icon}"></div>
          <span class="silo-item-title">${a.nome_arquivo}</span>
        </div>
        <div class="silo-item-actions">
          <button class="icon-download" title="Baixar" onclick="baixarArquivo('${urlArquivo}')"></button>
          <button class="icon-trash" title="Excluir" onclick="excluirArquivo(${a.id})"></button>
        </div>
      `;
      box.appendChild(div);
    });
  } catch (err) {
    console.error('Erro ao atualizar lista:', err);
    const box = document.querySelector('.silo-arquivos');
    box.innerHTML = '<p>❌ Falha ao comunicar com o servidor.</p>';
  }
}

// ===================================
// 🗑️ Excluir arquivo
// ===================================
async function excluirArquivo(id) {
  if (!confirm('Tem certeza que deseja excluir este arquivo?')) return;

  const fd = new FormData();
  fd.append('id', id);

  const res = await fetch('../funcoes/silo/excluir_arquivo.php', { method: 'POST', body: fd });
  const j = await res.json();

  if (j.ok) {
    alert('🗑️ Arquivo removido com sucesso!');
    atualizarLista();
    atualizarUso();
  } else {
    alert('❌ Erro: ' + j.err);
  }
}

// ===================================
// 📊 Atualiza uso do armazenamento
// ===================================
async function atualizarUso() {
  const res = await fetch('../funcoes/silo/get_uso.php');
  const j = await res.json();

  if (j.ok) {
    document.querySelector('.silo-info-title').innerText =
      `${j.percent}% utilizado (${j.usado} GB de ${j.limite} GB)`;
    document.querySelector('.silo-info-bar').style.background =
      `linear-gradient(to right, var(--verde) ${j.percent}%, transparent ${j.percent}%)`;
  }
}

// ===================================
// ⬇️ Baixar arquivo
// ===================================
function baixarArquivo(url) {
  const link = document.createElement('a');
  link.href = url;
  link.download = '';
  link.target = '_blank';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// ===================================
// 🧩 Define ícone conforme tipo
// ===================================
function getIconClass(tipo) {
  switch (tipo.toLowerCase()) {
    case 'pdf': return 'icon-pdf';
    case 'txt': return 'icon-txt';
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'gif': return 'icon-img';
    default: return 'icon-file';
  }
}
