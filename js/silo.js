// ================================
// 📦 Silo de Dados - Caderno de Campo
// ================================
document.addEventListener('DOMContentLoaded', () => {
  atualizarLista();
  atualizarUso();

  // 📤 Upload manual (compatível com mobile)
  const btnUpload = document.getElementById('btn-silo-arquivo');
  btnUpload.addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*,application/pdf,text/plain';

    input.onchange = async () => {
      const file = input.files[0];
      if (!file) return;
      const ok = await checarLimiteAntesUpload();
      if (ok) enviarArquivo(file);
      else abrirPopup('❌ Limite atingido', 'Exclua arquivos antes de enviar novos.');
    };

    input.click();
  });

  // 📸 Escanear documento (abrir câmera)
  const btnScan = document.getElementById('btn-silo-scan');
  btnScan.addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.capture = 'environment';

    input.onchange = async () => {
      const file = input.files[0];
      if (!file) return;
      const ok = await checarLimiteAntesUpload();
      if (ok) enviarArquivo(file, 'scan');
      else abrirPopup('❌ Limite atingido', 'Exclua arquivos antes de enviar novos.');
    };

    input.click();
  });
});

// ===================================
// 🚀 Upload com barra de progresso
// ===================================
async function enviarArquivo(file, origem = 'upload') {
  if (!file) return;

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

  xhr.upload.onprogress = e => {
    if (e.lengthComputable) {
      const percent = Math.round((e.loaded / e.total) * 100);
      bar.style.width = percent + '%';
      txt.textContent = percent + '%';
    }
  };

  cancelBtn.onclick = () => {
    xhr.abort();
    popup.remove();
    abrirPopup('⚠️ Upload cancelado', 'O envio foi interrompido pelo usuário.');
  };

  xhr.onload = () => {
    popup.remove();
    try {
      const j = JSON.parse(xhr.responseText);
      if (j.ok) {
        abrirPopup('✅ Arquivo enviado', 'O arquivo foi armazenado com sucesso.');
        atualizarLista();
        atualizarUso();
      } else {
        abrirPopup('❌ Erro ao enviar', j.msg || j.err || 'Falha desconhecida.');
      }
    } catch {
      console.error(xhr.responseText);
      abrirPopup('❌ Retorno inválido', 'O servidor retornou um formato inesperado.');
    }
  };

  xhr.onerror = () => {
    popup.remove();
    abrirPopup('❌ Erro de conexão', 'Não foi possível enviar o arquivo.');
  };

  xhr.send(fd);
}

// ===================================
// 📜 Atualiza lista (com ícones)
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
      const tipo = a.tipo_arquivo ? a.tipo_arquivo.split('/').pop().toLowerCase() : 'file';
      const icon = getIconClass(tipo);

      const div = document.createElement('div');
      div.className = 'silo-item-box';
      div.dataset.id = a.id;
      div.dataset.nome = a.nome_arquivo;
      div.dataset.tipo = a.tipo_arquivo;

      div.innerHTML = `
        <div class="silo-item silo-arquivo">
          <div class="btn-icon ${icon}"></div>
          <span class="silo-item-title">${a.nome_arquivo}</span>
        </div>
      `;

      div.addEventListener('click', (e) => {
        e.stopPropagation();
        abrirMenuArquivo(e, a);
      });

      box.appendChild(div);
    });
  } catch (err) {
    console.error('Erro ao atualizar lista:', err);
    document.querySelector('.silo-arquivos').innerHTML =
      '<p>❌ Falha ao comunicar com o servidor.</p>';
  }
}

// ===================================
// 🧩 Define ícone (usando SVGs locais)
// ===================================
function getIconClass(tipo) {
  tipo = tipo.toLowerCase();

  if (tipo.includes('pdf')) return 'icon-pdf';
  if (tipo.includes('jpg') || tipo.includes('jpeg') || tipo.includes('png') || tipo.includes('gif'))
    return 'icon-img';
  if (tipo.includes('txt')) return 'icon-txt';
  if (tipo.includes('zip') || tipo.includes('rar')) return 'icon-zip';
  if (tipo.includes('csv') || tipo.includes('xls') || tipo.includes('xlsx')) return 'icon-x';
  if (tipo.includes('doc') || tipo.includes('docx') || tipo.includes('ppt')) return 'icon-file';
  return 'icon-file';
}

// ===================================
// 📂 Menu de ações (Baixar / Renomear / Excluir)
// ===================================
function abrirMenuArquivo(e, arquivo) {
  e.stopPropagation();
  fecharMenuArquivo();

  const menu = document.createElement('div');
  menu.className = 'silo-menu-arquivo';
  menu.innerHTML = `
    <button class="menu-btn download">📥 Baixar</button>
    <button class="menu-btn rename">✏️ Renomear</button>
    <button class="menu-btn delete">🗑️ Excluir</button>
  `;

  document.body.appendChild(menu);
  menu.style.top = (e.clientY + window.scrollY + 10) + 'px';
  menu.style.left = (e.clientX + window.scrollX + 10) + 'px';

  menu.querySelector('.download').onclick = () => {
    baixarArquivo(`../funcoes/silo/download_arquivo.php?id=${arquivo.id}`);
    fecharMenuArquivo();
  };

  menu.querySelector('.rename').onclick = async () => {
    const novoNome = prompt('Digite o novo nome do arquivo:', arquivo.nome_arquivo);
    if (novoNome && novoNome.trim() !== '' && novoNome !== arquivo.nome_arquivo) {
      const fd = new FormData();
      fd.append('id', arquivo.id);
      fd.append('novo_nome', novoNome.trim());
      const res = await fetch('../funcoes/silo/rename_arquivo.php', { method: 'POST', body: fd });
      const j = await res.json();
      if (j.ok) {
        abrirPopup('✅ Arquivo renomeado', j.msg);
        atualizarLista();
      } else {
        abrirPopup('❌ Falha ao renomear', j.err || 'Erro desconhecido.');
      }
    }
    fecharMenuArquivo();
  };

  menu.querySelector('.delete').onclick = async () => {
    const confirm = await abrirPopupConfirm('Excluir arquivo?', 'Essa ação não poderá ser desfeita.');
    if (confirm) {
      excluirArquivo(arquivo.id);
    }
    fecharMenuArquivo();
  };

  document.addEventListener('click', fecharMenuArquivo, { once: true });
}

function fecharMenuArquivo() {
  const menus = document.querySelectorAll('.silo-menu-arquivo');
  menus.forEach(menu => menu.remove());
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
  const fd = new FormData();
  fd.append('id', id);
  const res = await fetch('../funcoes/silo/excluir_arquivo.php', { method: 'POST', body: fd });
  const j = await res.json();
  if (j.ok) {
    abrirPopup('🗑️ Arquivo excluído', 'O arquivo foi removido com sucesso.');
    atualizarLista();
    atualizarUso();
  } else {
    abrirPopup('❌ Falha ao excluir', j.err);
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
    abrirPopup('❌ Limite atingido', `Seu limite de ${j.limite} GB foi alcançado.`);
    return false;
  }
  return true;
}

// ===================================
// 🪟 Popups padronizados
// ===================================
function abrirPopup(titulo, mensagem) {
  fecharPopup();
  const popup = document.createElement('div');
  popup.className = 'popup-overlay';
  popup.innerHTML = `
    <div class="popup-container">
      <div class="popup-header">
        <h2 class="popup-title">${titulo}</h2>
        <button class="popup-close" onclick="fecharPopup()">×</button>
      </div>
      <div class="popup-body"><p class="popup-text">${mensagem}</p></div>
      <div class="popup-actions">
        <button class="popup-btn fundo-verde" onclick="fecharPopup()">Ok</button>
      </div>
    </div>`;
  document.body.appendChild(popup);
}

function fecharPopup() {
  const popup = document.querySelector('.popup-overlay');
  if (popup) popup.remove();
}

// 🔄 Popup com confirmação (resolve como Promise)
function abrirPopupConfirm(titulo, mensagem) {
  return new Promise(resolve => {
    fecharPopup();
    const popup = document.createElement('div');
    popup.className = 'popup-overlay';
    popup.innerHTML = `
      <div class="popup-container">
        <div class="popup-header">
          <h2 class="popup-title">${titulo}</h2>
          <button class="popup-close" onclick="fecharPopup();resolve(false)">×</button>
        </div>
        <div class="popup-body"><p class="popup-text">${mensagem}</p></div>
        <div class="popup-actions">
          <button class="popup-btn fundo-cinza-b" id="popup-cancel">Cancelar</button>
          <button class="popup-btn fundo-vermelho" id="popup-ok">Excluir</button>
        </div>
      </div>`;
    document.body.appendChild(popup);
    popup.querySelector('#popup-ok').onclick = () => { fecharPopup(); resolve(true); };
    popup.querySelector('#popup-cancel').onclick = () => { fecharPopup(); resolve(false); };
  });
}
