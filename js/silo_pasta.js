// ===================================
// 📂 Funções de Pastas - Silo de Dados
// ===================================

// 🌍 Global (usado também por upload e listar)
window.pastaAtual = parseInt(localStorage.getItem("silo_pastaAtual")) || 0;

document.addEventListener("DOMContentLoaded", () => {
  const btnCriarPasta = document.getElementById("btn-silo-pasta");
  if (btnCriarPasta) {
    btnCriarPasta.addEventListener("click", criarPasta);
  } else {
    console.warn("⚠️ Botão #btn-silo-pasta não encontrado.");
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
  fd.append("parent_id", window.pastaAtual || 0);

  try {
    const res = await fetch("../funcoes/silo/criar_pasta.php", {
      method: "POST",
      body: fd,
      credentials: "include",
    });

    const text = await res.text();
    console.log("📩 Retorno criar_pasta.php:", text);

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
  window.pastaAtual = parseInt(id) || 0;
  localStorage.setItem("silo_pastaAtual", window.pastaAtual);
  atualizarLista();
  atualizarBreadcrumb(nome);
  console.log("📂 Pasta atual definida:", window.pastaAtual);
}

// ================================
// 📂 Menu de ações da pasta
// ================================
function abrirMenuPasta(e, pasta) {
  e.stopPropagation();
  fecharMenuArquivo(); // Fecha menus anteriores

  const menu = document.createElement("div");
  menu.className = "silo-menu-arquivo";
  menu.innerHTML = `
    <button class="menu-btn acessar">📂 Acessar</button>
    <button class="menu-btn rename">✏️ Renomear</button>
    <button class="menu-btn delete">🗑️ Excluir</button>
  `;

  document.body.appendChild(menu);
  menu.style.top = (e.clientY + window.scrollY + 10) + "px";
  menu.style.left = (e.clientX + window.scrollX + 10) + "px";

  menu.querySelector(".acessar").onclick = () => {
    abrirPasta(pasta.id, pasta.nome_arquivo);
    fecharMenuArquivo();
  };

  menu.querySelector(".rename").onclick = async () => {
    const novoNome = prompt("Digite o novo nome da pasta:", pasta.nome_arquivo);
    if (!novoNome || novoNome.trim() === "" || novoNome === pasta.nome_arquivo) {
      fecharMenuArquivo();
      return;
    }

    const fd = new FormData();
    fd.append("id", pasta.id);
    fd.append("novo_nome", novoNome.trim());
    const res = await fetch("../funcoes/silo/rename_arquivo.php", { method: "POST", body: fd });
    const j = await res.json();

    if (j.ok) {
      abrirPopup("✅ Sucesso", j.msg);
      atualizarLista();
    } else {
      abrirPopup("❌ Erro", j.err || "Falha ao renomear pasta.");
    }

    fecharMenuArquivo();
  };

  menu.querySelector(".delete").onclick = async () => {
    if (!confirm("🗑️ Deseja realmente excluir esta pasta e todo o conteúdo dentro dela?")) {
      fecharMenuArquivo();
      return;
    }

    const fd = new FormData();
    fd.append("id", pasta.id);
    const res = await fetch("../funcoes/silo/excluir_arquivo.php", { method: "POST", body: fd });
    const j = await res.json();

    if (j.ok) {
      abrirPopup("🗑️ Removido", j.msg || "Pasta excluída com sucesso.");
      atualizarLista();
    } else {
      abrirPopup("❌ Erro", j.err || "Falha ao excluir pasta.");
    }

    fecharMenuArquivo();
  };

  document.addEventListener("click", fecharMenuArquivo, { once: true });
}

// ================================
// ⬅️ Voltar pasta anterior
// ================================
async function voltarPasta() {
  if (!window.pastaAtual || window.pastaAtual === 0) {
    window.pastaAtual = 0;
    localStorage.setItem("silo_pastaAtual", 0);
    atualizarLista();
    atualizarBreadcrumb();
    return;
  }

  try {
    const res = await fetch(`../funcoes/silo/get_parent.php?id=${window.pastaAtual}`);
    const j = await res.json();

    if (j.ok) {
      window.pastaAtual = j.parent_id || 0;
      localStorage.setItem("silo_pastaAtual", window.pastaAtual);
    } else {
      window.pastaAtual = 0;
      localStorage.setItem("silo_pastaAtual", 0);
    }

    atualizarLista();
    atualizarBreadcrumb();
  } catch (err) {
    console.error("Erro ao voltar pasta:", err);
    window.pastaAtual = 0;
    localStorage.setItem("silo_pastaAtual", 0);
    atualizarLista();
    atualizarBreadcrumb();
  }
}

// ================================
// 🧭 Atualiza breadcrumb
// ================================
async function atualizarBreadcrumb() {
  const nav = document.querySelector(".silo-breadcrumb");
  if (!nav) return;

  try {
    const pastaId = window.pastaAtual || 0;
    const res = await fetch(`../funcoes/silo/get_caminho.php?id=${pastaId}`);
    const j = await res.json();

    if (!j.ok || !Array.isArray(j.caminho) || j.caminho.length === 0) {
      nav.innerHTML = '<span class="breadcrumb-item is-current">Raiz</span>';
      return;
    }

    nav.innerHTML = "";
    j.caminho.forEach((p, i) => {
      if (i > 0) {
        const sep = document.createElement("span");
        sep.className = "silo-breadcrumb-sep";
        sep.textContent = "/";
        nav.appendChild(sep);
      }
      const span = document.createElement("span");
      span.textContent = p.nome;
      const isLast = i === j.caminho.length - 1;
      span.className = "breadcrumb-item" + (isLast ? " is-current" : "");
      if (!isLast) span.onclick = () => abrirPasta(p.id, p.nome);
      nav.appendChild(span);
    });
  } catch (err) {
    console.error("Erro ao atualizar breadcrumb:", err);
    nav.innerHTML = '<span class="breadcrumb-item is-current">Raiz</span>';
  }
}

// ================================
// 🧩 Ícone conforme tipo
// ================================
function getIconClass(tipo, isFolder = false) {
  if (isFolder || tipo === "pasta" || tipo === "folder") return "icon-pasta";
  tipo = tipo.toLowerCase();
  if (tipo.includes("pdf")) return "icon-pdf";
  if (tipo.includes("txt")) return "icon-txt";
  if (tipo.includes("image") || tipo === "jpg" || tipo === "jpeg" || tipo === "png")
    return "icon-img";
  return "icon-file";
}

// ================================
// 📢 Popup padrão
// ================================
function abrirPopup(titulo, mensagem) {
  const popup = document.createElement("div");
  popup.className = "popup-sistema";
  popup.innerHTML = `
    <div class="popup-box">
      <h3>${titulo}</h3>
      <p>${mensagem}</p>
      <button class="popup-fechar">Fechar</button>
    </div>
  `;
  document.body.appendChild(popup);
  popup.querySelector(".popup-fechar").onclick = () => popup.remove();
}
window.abrirPopup = abrirPopup;

// ================================
// 🧭 Atualiza lista
// ================================
async function atualizarLista() {
  const box = document.querySelector(".silo-arquivos-grid");

  if (!box) {
    console.warn("⚠️ .silo-arquivos-grid não encontrado no DOM.");
    return;
  }

  try {
    const res = await fetch(`../funcoes/silo/listar_arquivos.php?parent_id=${window.pastaAtual || 0}`);
    const j = await res.json();

    box.innerHTML = "";

    if (!j.ok || !Array.isArray(j.arquivos)) {
      console.error("Resposta inválida:", j);
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

    j.arquivos.forEach((a) => {
      const isFolder = a.is_folder === true;
      const icon = getIconClass(a.tipo_arquivo || "", isFolder);

      const div = document.createElement("div");
      div.className = "silo-item-box";
      div.dataset.id = a.id;

      div.innerHTML = `
        <div class="silo-item silo-arquivo">
          <div class="btn-icon ${icon}"></div>
          <span class="silo-item-title">${a.nome_exibicao}</span>
        </div>
      `;

      if (isFolder) {
        div.addEventListener("click", (e) => {
          e.stopPropagation();
          abrirMenuPasta(e, a);
        });
      } else {
        div.addEventListener("click", (e) => {
          e.stopPropagation();
          abrirMenuArquivo(e, a);
        });
      }

      box.appendChild(div);
    });

  } catch (err) {
    console.error("Erro ao atualizar lista:", err);
    box.innerHTML = '<p class="silo-state-msg">Falha ao comunicar com o servidor.</p>';
  }
}

window.atualizarLista = atualizarLista;
window.atualizarBreadcrumb = atualizarBreadcrumb;