// ================================
// 📦 Silo de Dados - Caderno de Campo
// ================================
document.addEventListener('DOMContentLoaded', () => {
  atualizarLista();
  atualizarUso();

  // 📤 Upload manual (compatível com mobile)
  const btnUpload = document.getElementById('btn-silo-arquivo');
  btnUpload.addEventListener('click', () => {
    // Cria input de arquivo
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*,application/pdf,text/plain';

    // Quando o usuário escolhe um arquivo
    input.onchange = async () => {
      const file = input.files[0];
      if (!file) return;

      // Checa limite só DEPOIS de o usuário escolher
      const ok = await checarLimiteAntesUpload();
      if (ok) enviarArquivo(file);
      else alert('Limite atingido. Exclua arquivos antes de enviar novos.');
    };

    // Abre seletor de arquivo
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
      else alert('Limite atingido. Exclua arquivos antes de enviar novos.');
    };

    input.click();
  });
});


// ===================================
// 🚀 Upload com barra de progresso
// ===================================
async function enviarArquivo(file, origem = 'upload') {
  const fd = new FormData();
  fd.append('arquivo', file);
  fd.append('origem', origem);
  fd.append('parent_id', pastaAtual || ""); // 🔥 adiciona pasta atual

  const xhr = new XMLHttpRequest();
  xhr.open('POST', '../funcoes/silo/upload_arquivo.php');
  xhr.onload = () => {
    try {
      const j = JSON.parse(xhr.responseText);
      if (j.ok) {
        abrirPopup("✅ Enviado", "Arquivo enviado com sucesso!");
        atualizarLista();
      } else {
        abrirPopup("❌ Erro", j.err || "Falha no upload.");
      }
    } catch (err) {
      abrirPopup("❌ Retorno inválido", xhr.responseText);
    }
  };
  xhr.send(fd);
}

// ===================================
// 📜 Atualiza lista (com ícones por tipo e suporte a pastas)
// ===================================
async function atualizarLista() {
  try {
    // 🔹 Busca arquivos da pasta atual (ou raiz)
    const res = await fetch(`../funcoes/silo/listar_arquivos.php?pasta=${pastaAtual || ''}`);
    const j = await res.json();
    const box = document.querySelector('.silo-arquivos');
    box.innerHTML = '';

    if (!j.ok || !Array.isArray(j.arquivos)) {
      console.error('Resposta inválida:', j);
      box.innerHTML = '<p>❌ Erro ao carregar arquivos.</p>';
      return;
    }

    if (j.arquivos.length === 0) {
      box.innerHTML = '<p style="text-align:center; opacity:0.6;">Nenhum item nesta pasta.</p>';
      return;
    }

    // 🔹 Separa pastas e arquivos
    const pastas = j.arquivos.filter(a => a.tipo_arquivo === 'folder');
    const arquivos = j.arquivos.filter(a => a.tipo_arquivo !== 'folder');

    // 🔹 Exibe pastas primeiro
    [...pastas, ...arquivos].forEach(a => {
      const isFolder = a.tipo_arquivo === 'folder';
      const icon = isFolder
        ? 'icon-folder'
        : getIconClass(a.tipo_arquivo || 'file');

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

      // 🔹 Clique normal → menu de ações
      div.addEventListener('click', (e) => {
        e.stopPropagation();
        abrirMenuArquivo(e, a);
      });

      // 🔹 Duplo clique → entra na pasta
      if (isFolder && typeof acessarPasta === 'function') {
        div.addEventListener('dblclick', (e) => {
          e.stopPropagation();
          acessarPasta(a.id);
        });
      }

      box.appendChild(div);
    });

    // 🔹 Atualiza cabeçalho de navegação (breadcrumb)
    atualizarBreadcrumb();

  } catch (err) {
    console.error('Erro ao atualizar lista:', err);
    document.querySelector('.silo-arquivos').innerHTML =
      '<p>❌ Falha ao comunicar com o servidor.</p>';
  }
}


// ===================================
// 🧩 Define ícone conforme tipo de arquivo (usando seus SVGs)
// ===================================
function getIconClass(tipo) {
  tipo = tipo.toLowerCase();

  if (tipo.includes('pdf')) return 'icon-pdf'; // 📄 PDF
  if (tipo.includes('jpg') || tipo.includes('jpeg') || tipo.includes('png') || tipo.includes('gif'))
    return 'icon-img'; // 🖼️ Imagem
  if (tipo.includes('txt')) return 'icon-txt'; // 📜 Texto
  if (tipo.includes('zip') || tipo.includes('rar')) return 'icon-zip'; // 📦 Compactado
  if (tipo.includes('csv') || tipo.includes('xls') || tipo.includes('xlsx'))
    return 'icon-x'; // 📗 Planilhas
  if (tipo.includes('doc') || tipo.includes('docx'))
    return 'icon-file'; // 📘 Word
  if (tipo.includes('ppt') || tipo.includes('pptx'))
    return 'icon-file'; // 🧾 PowerPoint

  return 'icon-file'; // Padrão
}



// ===================================
// 📂 Menu de ações (Baixar / Renomear / Mover / Excluir)
// ===================================
function abrirMenuArquivo(e, arquivo) {
  e.stopPropagation();
  fecharMenuArquivo();

  const menu = document.createElement('div');
  menu.className = 'silo-menu-arquivo';
  menu.innerHTML = `
    <button class="menu-btn download">📥 Baixar</button>
    <button class="menu-btn rename">✏️ Renomear</button>
    <button class="menu-btn mover">📂 Mover</button>
    <button class="menu-btn delete">🗑️ Excluir</button>
  `;

  document.body.appendChild(menu);
  menu.style.top = (e.clientY + window.scrollY + 10) + 'px';
  menu.style.left = (e.clientX + window.scrollX + 10) + 'px';

  // 📥 Baixar arquivo
  menu.querySelector('.download').onclick = () => {
    baixarArquivo(`../funcoes/silo/download_arquivo.php?id=${arquivo.id}`);
    fecharMenuArquivo();
  };

  // ✏️ Renomear arquivo
  menu.querySelector('.rename').onclick = async () => {
    const novoNome = prompt('Digite o novo nome do arquivo:', arquivo.nome_arquivo);
    if (novoNome && novoNome.trim() !== '' && novoNome !== arquivo.nome_arquivo) {
      const fd = new FormData();
      fd.append('id', arquivo.id);
      fd.append('novo_nome', novoNome.trim());
      const res = await fetch('../funcoes/silo/rename_arquivo.php', { method: 'POST', body: fd });
      const j = await res.json();
      if (j.ok) {
        abrirPopup('✅ Sucesso', j.msg);
        atualizarLista();
      } else {
        abrirPopup('❌ Erro', j.err || 'Erro ao renomear.');
      }
    }
    fecharMenuArquivo();
  };

  // 📂 Mover arquivo/pasta
  menu.querySelector('.mover').onclick = () => {
    moverItem(arquivo.id); // função vinda do silo_mover.js
    fecharMenuArquivo();
  };

  // 🗑️ Excluir
  menu.querySelector('.delete').onclick = () => {
    excluirArquivo(arquivo.id);
    fecharMenuArquivo();
  };

  document.addEventListener('click', fecharMenuArquivo, { once: true });
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
// ===================================
// ❌ Fecha qualquer menu de arquivo aberto
// ===================================
function fecharMenuArquivo() {
  const menu = document.querySelector('.silo-menu-arquivo');
  if (menu) menu.remove();
}
