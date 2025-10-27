// ================================
// 📁 Gerenciamento de Pastas - Silo de Dados
// ================================

let pastaAtual = '';      // ID ou referência da pasta atual (vazia = raiz)
let caminhoStack = [];    // Histórico de navegação

document.addEventListener('DOMContentLoaded', () => {
  const btnCriar = document.getElementById('btn-silo-pasta');
  const btnMover = document.getElementById('btn-silo-mover');
  const btnVoltar = document.getElementById('btn-silo-voltar');

  if (btnCriar) btnCriar.addEventListener('click', criarPasta);
  if (btnMover) btnMover.addEventListener('click', abrirMoverArquivo);
  if (btnVoltar) btnVoltar.addEventListener('click', voltarPasta);
});

// ================================
// 📂 Criar nova pasta
// ================================
async function criarPasta() {
  const nome = prompt("Digite o nome da nova pasta:");
  if (!nome || nome.trim() === "") return;

  const fd = new FormData();
  fd.append("nome", nome.trim());
  fd.append("parent_id", pastaAtual || "");

  try {
    const res = await fetch("../funcoes/silo/criar_pasta.php", { method: "POST", body: fd });
    const j = await res.json();

    if (j.ok) {
      abrirPopup("📁 Pasta criada", j.msg);
      atualizarLista(); // Função existente no silo.js
    } else {
      abrirPopup("❌ Erro", j.err);
    }
  } catch (err) {
    console.error("Erro ao criar pasta:", err);
    abrirPopup("❌ Falha", "Não foi possível criar a pasta.");
  }
}

// ================================
// 🚪 Acessar pasta (ao dar duplo clique)
// ================================
function acessarPasta(id) {
  pastaAtual = id;
  caminhoStack.push(id);
  atualizarLista();
}

// ================================
// 🔙 Voltar para pasta anterior
// ================================
function voltarPasta() {
  if (caminhoStack.length > 0) caminhoStack.pop();
  pastaAtual = caminhoStack[caminhoStack.length - 1] || '';
  atualizarLista();
}

// ================================
// 🔄 Mover arquivo (básico)
// ================================
async function abrirMoverArquivo() {
  const id = prompt("Informe o ID do arquivo que deseja mover:");
  if (!id) return;

  const destino = prompt("Informe o ID da pasta destino:");
  if (!destino) return;

  const fd = new FormData();
  fd.append("id", id);
  fd.append("destino_id", destino);

  try {
    const res = await fetch("../funcoes/silo/mover_arquivo.php", { method: "POST", body: fd });
    const j = await res.json();

    if (j.ok) {
      abrirPopup("✅ Arquivo movido", j.msg);
      atualizarLista();
    } else {
      abrirPopup("❌ Erro", j.err);
    }
  } catch (err) {
    console.error("Erro ao mover arquivo:", err);
    abrirPopup("❌ Falha", "Não foi possível mover o arquivo.");
  }
}

// ================================
// 📁 Duplicar função de clique da pasta
// ================================
function configurarAcessoPastas() {
  document.querySelectorAll('.silo-item-box[data-tipo="folder"]').forEach(el => {
    el.addEventListener('dblclick', () => acessarPasta(el.dataset.id));
  });
}
