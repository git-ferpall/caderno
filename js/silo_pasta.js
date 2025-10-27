// ===================================
// 📂 Funções de Pastas - Silo de Dados
// ===================================

let pastaAtual = ''; // raiz padrão
window.siloPastaAtual = pastaAtual; // 🔄 variável global de referência

document.addEventListener('DOMContentLoaded', () => {
  const btnCriarPasta = document.getElementById('btn-silo-pasta');
  if (btnCriarPasta) {
    btnCriarPasta.addEventListener('click', criarPasta);
  } else {
    console.warn('⚠️ Botão #btn-silo-pasta não encontrado.');
  }

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
      await atualizarLista();
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
// 📂 Abrir pasta
// ================================
function abrirPasta(id, nome) {
  pastaAtual = id;
  window.siloPastaAtual = id; // 🔄 sincroniza com o mover.js
  console.log(`📂 Entrando na pasta: ${nome} (ID: ${id})`);
  atualizarLista();
  atualizarBreadcrumb(nome);
}

// ================================
// ⬅️ Voltar uma pasta
// ================================
async function voltarPasta() {
  if (!pastaAtual || pastaAtual === '') {
    pastaAtual = '';
    window.siloPastaAtual = pastaAtual;
    atualizarLista();
    atualizarBreadcrumb();
    return;
  }

  try {
    const res = await fetch(`../funcoes/silo/get_parent.php?id=${pastaAtual}`);
    const j = await res.json();

    if (j.ok) {
      pastaAtual = j.parent_id || '';
      window.siloPastaAtual = pastaAtual;
      atualizarLista();
      atualizarBreadcrumb();
    } else {
      pastaAtual = '';
      window.siloPastaAtual = '';
      atualizarLista();
      atualizarBreadcrumb();
    }
  } catch (err) {
    console.error('Erro ao voltar pasta:', err);
    pastaAtual = '';
    window.siloPastaAtual = '';
    atualizarLista();
    atualizarBreadcrumb();
  }
}

// ================================
// 🧭 Atualiza breadcrumb hierárquico
// ================================
async function atualizarBreadcrumb() {
  const breadcrumb = document.querySelector('.silo-breadcrumb');
  if (!breadcrumb) return;

  if (!pastaAtual || pastaAtual === '') {
    breadcrumb.innerHTML = `<span>📁 Raiz</span>`;
    return;
  }

  try {
    const res = await fetch(`../funcoes/silo/get_caminho.php?id=${pastaAtual}`);
    const j = await res.json();

    if (!j.ok) {
      breadcrumb.innerHTML = `<span>📁 Raiz</span>`;
      return;
    }

    let html = `<span class="link-voltar" onclick="voltarPasta()">⬅️ Voltar</span>`;
    html += `<span style="opacity:0.6;"> / </span>`;
    html += `<span class="breadcrumb-item link" onclick="abrirPasta('', 'Raiz')">📁 Raiz</span>`;

    j.caminho.forEach(p => {
      html += ` <span style="opacity:0.6;">/</span> `;
      html += `<span class="breadcrumb-item link" onclick="abrirPasta(${p.id}, '${p.nome.replace(/'/g, "\\'")}')">${p.nome}</span>`;
    });

    breadcrumb.innerHTML = html;
  } catch (err) {
    console.error('Erro ao atualizar breadcrumb:', err);
    breadcrumb.innerHTML = `<span>📁 Raiz</span>`;
  }
}

// ================================
// 🧩 Ícone conforme tipo
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
// 📢 Popup padrão
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
// 🧭 Atualiza lista principal
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
        div.addEventListener('click', (e) => {
          e.stopPropagation();
          abrirMenuPasta(e, a);
        });
      } else {
        div.addEventListener('click', (e) => {
          e.stopPropagation();
          abrirMenuArquivo(e, a);
        });
      }

      box.appendChild(div);
    });

    console.log(`📁 Lista atualizada — ${j.arquivos.length} itens (Pasta atual: ${pastaAtual || 'raiz'})`);

  } catch (err) {
    console.error('Erro ao atualizar lista:', err);
    document.querySelector('.silo-arquivos').innerHTML =
      '<p>❌ Falha ao comunicar com o servidor.</p>';
  }
}
