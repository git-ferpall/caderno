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
      abrirPopupSistema("📁 Sucesso", j.msg || "Pasta criada com sucesso!");
      atualizarLista();
    } else {
      abrirPopupSistema("❌ Erro", j.err || "Falha ao criar pasta.");
    }
  } catch (err) {
    console.error("Erro ao criar pasta:", err);
    abrirPopupSistema("❌ Erro", "Falha ao comunicar com o servidor.");
  }
}
window.criarPasta = criarPasta; // garante visibilidade global


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
  if (isFolder) return 'icon-folder';
  tipo = tipo.toLowerCase();
  if (tipo.includes('pdf')) return 'icon-pdf';
  if (tipo.includes('txt')) return 'icon-txt';
  if (tipo.includes('image') || tipo === 'jpg' || tipo === 'jpeg' || tipo === 'png')
    return 'icon-img';
  return 'icon-file';
}

// ================================
// 📢 Popup genérico do sistema
// ================================
function abrirPopupSistema(titulo, mensagem) {
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
