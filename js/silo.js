// ================================
// 📦 Silo de Dados - Caderno de Campo
// ================================
const escapeHtml = (value) =>
  (window.CadernoUtils && window.CadernoUtils.escapeHtml
    ? window.CadernoUtils.escapeHtml(value)
    : String(value ?? ""));

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
      if (typeof window.atualizarLista === 'function') window.atualizarLista();
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



// moverItem definido em silo_mover.js
// ❌ Fecha menus abertos
// ===================================
function fecharMenuArquivo() {
  const menu = document.querySelector('.silo-menu-arquivo');
  if (menu) menu.remove();
}
