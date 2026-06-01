// =============================================
// 🔍 Silo de Dados - Busca de Arquivos e Pastas
// =============================================

document.addEventListener("DOMContentLoaded", () => {
  const inputBusca = document.getElementById("siloBusca");
  const boxArquivos = document.querySelector(".silo-arquivos-grid");

  if (!inputBusca || !boxArquivos) {
    console.warn("⚠️ Campo de busca ou container de arquivos não encontrado.");
    return;
  }

  let timer;

  // 🔎 Evento de digitação com debounce
  inputBusca.addEventListener("input", () => {
    clearTimeout(timer);
    const termo = inputBusca.value.trim();
    timer = setTimeout(() => {
      if (termo === "") {
        atualizarLista(); // volta para listagem normal
      } else {
        buscarArquivos(termo);
      }
    }, 350);
  });
});

// =============================================
// 🚀 Função principal de busca
// =============================================
function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

async function buscarArquivos(termo) {
  const box = document.querySelector(".silo-arquivos-grid");
  if (!box) return;

  const termoSafe = escapeHtml(termo);

  box.innerHTML = `
    <div class="silo-state-msg">
      <div class="silo-loader"></div>
      <p>Buscando "<strong>${termoSafe}</strong>"...</p>
    </div>
  `;

  try {
    const res = await fetch(`../funcoes/silo/buscar_arquivos.php?q=${encodeURIComponent(termo)}`);
    const j = await res.json();

    if (!j.ok) throw new Error(j.err || "Falha na busca.");

    if (!j.arquivos || j.arquivos.length === 0) {
      box.innerHTML = `<p class="silo-state-msg">Nenhum resultado para "<strong>${termoSafe}</strong>".</p>`;
      return;
    }

    // Limpa resultados anteriores
    box.innerHTML = "";

    // Exibe os resultados
    j.arquivos.forEach((a) => {
      const isFolder = a.tipo_arquivo === "folder";
      const icon = isFolder ? "icon-folder" : getIconClass(a.tipo_arquivo || "file");

      const div = document.createElement("div");
      div.className = "silo-item-box";
      div.dataset.id = a.id;
      div.dataset.nome = a.nome_arquivo;
      div.dataset.tipo = a.tipo_arquivo;

      div.innerHTML = `
        <div class="silo-item silo-arquivo">
          <div class="btn-icon ${icon}"></div>
          <div>
            <span class="silo-item-title">${a.nome_exibicao || escapeHtml(a.nome_arquivo)}</span>
            <span class="silo-item-path">${formatarCaminho(a.caminho_arquivo)}</span>
          </div>
        </div>
      `;

      // Clique → menu de ações padrão
      div.addEventListener("click", (e) => {
        e.stopPropagation();
        abrirMenuArquivo(e, a);
      });

      // Duplo clique → entrar em pasta, se for pasta
      if (isFolder) {
        div.addEventListener("dblclick", (e) => {
          e.stopPropagation();
          acessarPasta(a.id);
        });
      }

      box.appendChild(div);
    });
  } catch (err) {
    console.error("❌ Erro na busca:", err);
    const box = document.querySelector(".silo-arquivos-grid");
    if (box) box.innerHTML = '<p class="silo-state-msg">Erro ao buscar arquivos.</p>';
  }
}

// =============================================
// 🧭 Remove prefixo "silo/USERID" e formata caminho
// =============================================
function formatarCaminho(caminho) {
  if (!caminho) return "";
  // Remove "silo/xxxx/" (onde xxxx é o ID do usuário)
  return caminho.replace(/^silo\/\d+\//, "");
}

// =============================================
// 🎨 Loader simples CSS (adicione no seu style.css)
// =============================================
// .loader {
//   border: 4px solid #f3f3f3;
//   border-top: 4px solid var(--verde);
//   border-radius: 50%;
//   width: 24px;
//   height: 24px;
//   animation: spin 1s linear infinite;
//   margin: 0 auto 10px;
// }
// @keyframes spin {
//   0% { transform: rotate(0deg); }
//   100% { transform: rotate(360deg); }
// }
