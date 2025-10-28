// ================================
// 📦 Silo de Dados - Caderno de Campo
// ================================
document.addEventListener('DOMContentLoaded', () => {
  atualizarLista();
  atualizarUso();
  atualizarBreadcrumb();
});

// ===================================
// 📜 Atualiza lista de arquivos/pastas
// ===================================
async function atualizarLista() {
  try {
    const res = await fetch(`../funcoes/silo/listar_arquivos.php?parent_id=${window.pastaAtual || 0}`);
    const j = await res.json();
    const box = document.querySelector('.silo-arquivos');
    box.innerHTML = '';

    if (!j.ok || !Array.isArray(j.arquivos)) {
      box.innerHTML = '<p>❌ Erro ao carregar arquivos.</p>';
      return;
    }

    if (j.arquivos.length === 0) {
      box.innerHTML = '<p style="text-align:center; opacity:0.6;">Nenhum item nesta pasta.</p>';
      return;
    }

    const pastas = j.arquivos.filter(a => a.tipo_arquivo === 'folder');
    const arquivos = j.arquivos.filter(a => a.tipo_arquivo !== 'folder');

    [...pastas, ...arquivos].forEach(a => {
      const isFolder = a.tipo_arquivo === 'folder';
      const icon = isFolder ? 'icon-folder' : getIconClass(a.tipo_arquivo || 'file');

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

      // Clique → menu de ações
      div.addEventListener('click', (e) => {
        e.stopPropagation();
        abrirMenuArquivo(e, a);
      });

      // Duplo clique → entrar em pasta
      if (isFolder) {
        div.addEventListener('dblclick', (e) => {
          e.stopPropagation();
          acessarPasta(a.id);
        });
      }

      box.appendChild(div);
    });

    atualizarBreadcrumb();
  } catch (err) {
    console.error('Erro ao atualizar lista:', err);
    document.querySelector('.silo-arquivos').innerHTML =
      '<p>❌ Falha ao comunicar com o servidor.</p>';
  }
}

// ===================================
// 🧩 Ícones conforme tipo
// ===================================
function getIconClass(tipo) {
  tipo = tipo.toLowerCase();
  if (tipo.includes('pdf')) return 'icon-pdf';
  if (tipo.includes('jpg') || tipo.includes('jpeg') || tipo.includes('png') || tipo.includes('gif')) return 'icon-img';
  if (tipo.includes('txt')) return 'icon-txt';
  if (tipo.includes('zip') || tipo.includes('rar')) return 'icon-zip';
  if (tipo.includes('csv') || tipo.includes('xls') || tipo.includes('xlsx')) return 'icon-x';
  if (tipo.includes('doc') || tipo.includes('docx')) return 'icon-file';
  if (tipo.includes('ppt') || tipo.includes('pptx')) return 'icon-file';
  return 'icon-file';
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

  // 📥 Baixar
  menu.querySelector('.download').onclick = () => {
    baixarArquivo(`../funcoes/silo/download_arquivo.php?id=${arquivo.id}`);
    fecharMenuArquivo();
  };

  // ✏️ Renomear
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

  // 📂 Mover
  menu.querySelector('.mover').onclick = () => {
    moverItem(arquivo.id);
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
// 📥 Baixar
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
// 🗑️ Excluir
// ===================================
async function excluirArquivo(id) {
  if (!confirm('Excluir este arquivo?')) return;
  const fd = new FormData();
  fd.append('id', id);
  const res = await fetch('../funcoes/silo/excluir_arquivo.php', { method: 'POST', body: fd });
  const j = await res.json();
  if (j.ok) {
    abrirPopup('🗑️ Removido', j.msg || 'Arquivo excluído.');
    atualizarLista();
    atualizarUso();
  } else {
    abrirPopup('❌ Erro', j.err || 'Falha ao excluir arquivo.');
  }
}

// ===================================
// 📊 Atualiza uso de espaço
// ===================================
async function atualizarUso() {
  const res = await fetch('../funcoes/silo/get_uso.php');
  const j = await res.json();
  if (j.ok) {
    const usado = parseFloat(j.usado).toFixed(2);
    const limite = parseFloat(j.limite).toFixed(2);
    document.querySelector('.silo-info-title').innerText =
      `${j.percent}% utilizado (${usado} GB de ${limite} GB)`;
    document.querySelector('.silo-info-bar').style.background =
      `linear-gradient(to right, var(--verde) ${j.percent}%, transparent ${j.percent}%)`;
  }
}

// ===================================
// 📁 Navegação
// ===================================
window.pastaAtual = 0;

function acessarPasta(id) {
  window.pastaAtual = id;
  atualizarLista();
  atualizarBreadcrumb();
  console.log("📁 Pasta atual:", id);
}

// ===================================
// 🧭 Breadcrumb (caminho de navegação)
// ===================================
async function atualizarBreadcrumb() {
  const nav = document.querySelector('.silo-breadcrumb');
  if (!nav) return;

  try {
    const res = await fetch(`../funcoes/silo/get_caminho.php?pasta_id=${window.pastaAtual || 0}`);
    const j = await res.json();
    if (j.ok) {
      nav.innerHTML = '';
      j.caminho.forEach((p, i) => {
        const span = document.createElement('span');
        span.textContent = p.nome;
        span.className = 'breadcrumb-item';
        span.onclick = () => acessarPasta(p.id);
        nav.appendChild(span);
        if (i < j.caminho.length - 1) nav.innerHTML += ' / ';
      });
    } else {
      nav.innerHTML = 'Silo de Dados';
    }
  } catch {
    nav.innerHTML = 'Silo de Dados';
  }
}

// ===================================
// 📂 Mover arquivo/pasta
// ===================================
async function moverItem(id) {
  const destino = prompt("Digite o ID da pasta de destino:");
  if (!destino || isNaN(destino)) return;
  const fd = new FormData();
  fd.append('id', id);
  fd.append('destino', destino);
  const res = await fetch('../funcoes/silo/mover_arquivo.php', { method: 'POST', body: fd });
  const j = await res.json();
  if (j.ok) {
    abrirPopup('📂 Movido', j.msg);
    atualizarLista();
  } else abrirPopup('❌ Erro', j.err);
}

// ===================================
// ❌ Fecha menus abertos
// ===================================
function fecharMenuArquivo() {
  const menu = document.querySelector('.silo-menu-arquivo');
  if (menu) menu.remove();
}

// Exporta funções globais
window.atualizarLista = atualizarLista;
window.atualizarUso = atualizarUso;
window.atualizarBreadcrumb = atualizarBreadcrumb;
