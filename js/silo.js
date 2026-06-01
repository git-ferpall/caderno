// ================================
// 📦 Silo de Dados - Caderno de Campo
// ================================
document.addEventListener('DOMContentLoaded', () => {
  // 🧠 Restaura última pasta acessada (persistência)
  const ultima = localStorage.getItem("silo_pastaAtual");
  window.pastaAtual = ultima ? parseInt(ultima) : 0;

  atualizarLista();
  atualizarBreadcrumb();
});

// ===================================
// 📜 Atualiza lista de arquivos/pastas
// ===================================
async function atualizarLista() {
  try {
    const box = document.querySelector('.silo-arquivos-grid');
    if (!box) return;
    box.innerHTML = '';

    const res = await fetch(`../funcoes/silo/listar_arquivos.php?parent_id=${window.pastaAtual || 0}`);
    const j = await res.json();

    if (!j.ok || !Array.isArray(j.arquivos)) {
      box.innerHTML = '<p class="silo-state-msg">Erro ao carregar arquivos.</p>';
      return;
    }

    if (j.arquivos.length === 0) {
      box.innerHTML = `
        <div class="silo-empty">
          <div class="silo-empty-icon">📁</div>
          <p>Esta pasta está vazia</p>
          <small>Use o botão + para enviar arquivos ou criar uma pasta</small>
        </div>`;
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
    const box = document.querySelector('.silo-arquivos-grid');
    if (box) box.innerHTML = '<p class="silo-state-msg">Falha ao comunicar com o servidor.</p>';
  }
}

// ===================================
// 📁 Acessar e manter pasta atual
// ===================================
function acessarPasta(id) {
  window.pastaAtual = parseInt(id);
  localStorage.setItem("silo_pastaAtual", id); // salva no navegador
  atualizarLista();
  atualizarBreadcrumb();
  console.log("📁 Pasta atual definida:", id);
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
    fecharMenuArquivo();
    const novoNome = await siloPrompt({
      title: 'Renomear arquivo',
      label: 'Novo nome',
      defaultValue: arquivo.nome_arquivo,
    });
    if (!novoNome || novoNome === arquivo.nome_arquivo) return;

    const fd = new FormData();
    fd.append('id', arquivo.id);
    fd.append('novo_nome', novoNome);
    const res = await fetch('../funcoes/silo/rename_arquivo.php', { method: 'POST', body: fd });
    const j = await res.json();
    if (j.ok) {
      siloShowSuccess(j.msg || 'Arquivo renomeado com sucesso!');
      atualizarLista();
    } else {
      siloShowError(j.err || 'Erro ao renomear.');
    }
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
  siloConfirm({
    title: 'Excluir arquivo?',
    text: 'Esta ação não poderá ser desfeita.',
    onConfirm: async () => {
      const fd = new FormData();
      fd.append('id', id);
      const res = await fetch('../funcoes/silo/excluir_arquivo.php', { method: 'POST', body: fd });
      const j = await res.json();
      if (j.ok) {
        await siloRefreshLista();
        siloRefreshUso();
        siloShowSuccess(j.msg || "Arquivo excluído.");
      } else {
        siloShowError(j.err || 'Falha ao excluir arquivo.');
      }
    },
  });
}



// ===================================
// 🧭 Breadcrumb
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
        if (i > 0) {
          const sep = document.createElement('span');
          sep.className = 'silo-breadcrumb-sep';
          sep.textContent = '/';
          nav.appendChild(sep);
        }
        const span = document.createElement('span');
        span.textContent = p.nome;
        const isLast = i === j.caminho.length - 1;
        span.className = 'breadcrumb-item' + (isLast ? ' is-current' : '');
        if (!isLast) span.onclick = () => acessarPasta(p.id);
        nav.appendChild(span);
      });
    } else {
      nav.innerHTML = '<span class="breadcrumb-item is-current">Silo de Dados</span>';
    }
  } catch {
    nav.innerHTML = 'Silo de Dados';
  }
}

// moverItem definido em silo_mover.js
// ❌ Fecha menus abertos
// ===================================
function fecharMenuArquivo() {
  const menu = document.querySelector('.silo-menu-arquivo');
  if (menu) menu.remove();
}
