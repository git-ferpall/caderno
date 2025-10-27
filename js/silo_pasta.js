// ===================================
// 📂 Funções de Pastas - Silo de Dados
// ===================================

let pastaAtual = ''; // raiz padrão

document.addEventListener('DOMContentLoaded', () => {
  const btnCriarPasta = document.getElementById('btn-silo-pasta');
  if (btnCriarPasta) {
    btnCriarPasta.addEventListener('click', criarPasta);
  } else {
    console.warn('⚠️ Botão #btn-silo-pasta não encontrado.');
  }

  // atualiza breadcrumb na inicialização
  atualizarBreadcrumb();
});

// ================================
// 📁 Criar nova pasta
// ================================
async function criarPasta() {
  const nome = prompt("📁 Nome da nova pasta:");
  if (!nome || nome.trim() === "") return;

  const fd = new FormData();
  fd.append("nome", nome.trim());
  fd.append("parent_id", pastaAtual || "");

  try {
    const res = await fetch("../funcoes/silo/criar_pasta.php", {
      method: "POST",
      body: fd,
      credentials: "include"
    });

    const text = await res.text();
    console.log('📩 Retorno criar_pasta.php:', text);

    const j = JSON.parse(text);

    if (j.ok) {
      abrirPopup("📁 Sucesso", j.msg || "Pasta criada com sucesso!");
      atualizarLista();
    } else {
      abrirPopup("❌ Erro", j.err || "Falha ao criar pasta.");
    }
  } catch (err) {
    console.error("Erro ao criar pasta:", err);
    abrirPopup("❌ Erro", "Falha ao comunicar com o servidor.");
  }
}
window.criarPasta = criarPasta;

// ================================
// 📂 Abrir pasta (entrar)
// ================================
function abrirPasta(id, nome) {
  pastaAtual = id;
  atualizarLista();
  atualizarBreadcrumb(nome);
}

// ================================
// ⬅️ Voltar para pasta anterior
// ================================
function voltarPasta() {
  pastaAtual = ''; // volta para raiz
  atualizarLista();
  atualizarBreadcrumb();
}

// ================================
// 🧭 Atualiza breadcrumb de navegação
// ================================
function atualizarBreadcrumb(nomeAtual = null) {
  const breadcrumb = document.querySelector('.silo-breadcrumb');
  if (!breadcrumb) return;

  if (!pastaAtual || pastaAtual === '') {
    breadcrumb.innerHTML = `<span>📁 Raiz</span>`;
  } else {
    breadcrumb.innerHTML = `
      <span class="link-voltar" onclick="voltarPasta()">⬅️ Voltar</span>
      <span style="opacity:0.6;"> / </span>
      <span>📂 ${nomeAtual || 'Pasta atual'}</span>
    `;
  }
}

// ================================
// 🧩 Ícone conforme tipo de item
// ================================
function getIconClass(tipo, isFolder = false) {
  if (isFolder || tipo === 'pasta' || tipo === 'folder') return 'icon-pasta';
  tipo = tipo.toLowerCase();
  if (tipo.includes('pdf')) return 'icon-pdf';
  if (tipo.includes('txt')) return 'icon-txt';
  if (tipo.includes('image') || tipo === 'jpg' || tipo === 'jpeg' || tipo === 'png')
    return 'icon-img';
  return 'icon-file';
}

// ================================
// 📢 Popup padrão do sistema
// ================================
function abrirPopup(titulo, mensagem) {
  const popup = document.createElement('div');
  popup.className = 'popup-sistema';
  popup.innerHTML = `
    <div class="popup-box">
      <h3>${titulo}</h3>
      <p>${mensagem}</p>
      <button class="popup-fechar">Fechar</button>
    </div>
  `;
  document.body.appendChild(popup);
  popup.querySelector('.popup-fechar').onclick = () => popup.remove();
}
window.abrirPopup = abrirPopup;

// ================================
// 🧭 Adapta listar_arquivos() para suportar navegação
// ================================
async function atualizarLista() {
  try {
    const res = await fetch(`../funcoes/silo/listar_arquivos.php?parent_id=${pastaAtual || ''}`);
    const j = await res.json();
    const box = document.querySelector('.silo-arquivos');
    box.innerHTML = '';

    if (!j.ok || !Array.isArray(j.arquivos)) {
      console.error('Resposta inválida:', j);
      box.innerHTML = '<p>❌ Erro ao carregar arquivos.</p>';
      return;
    }

    if (j.arquivos.length === 0) {
      box.innerHTML = '<p style="text-align:center; opacity:0.6;">Nenhum item encontrado.</p>';
      return;
    }

    j.arquivos.forEach(a => {
      const isFolder = a.tipo === 'pasta' || a.tipo_arquivo === 'folder';
      const icon = getIconClass(a.tipo_arquivo || '', isFolder);

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

      if (isFolder) {
        // abre a pasta
        div.addEventListener('click', () => abrirPasta(a.id, a.nome_arquivo));
      } else {
        // abre menu (download, renomear, excluir)
        div.addEventListener('click', (e) => {
          e.stopPropagation();
          abrirMenuArquivo(e, a);
        });
      }

      box.appendChild(div);
    });
  } catch (err) {
    console.error('Erro ao atualizar lista:', err);
    document.querySelector('.silo-arquivos').innerHTML =
      '<p>❌ Falha ao comunicar com o servidor.</p>';
  }
}
