/**
 * HIDROPONIA.JS v3.0
 * Estufas, bancadas (multi-produto), QR Code e deep link ?b={id}
 */
document.addEventListener("DOMContentLoaded", () => {
  initEstufaSave();
  initBancadaSave();
  initQrBancada();
  abrirBancadaPorQr();
  initExcluirHidroponia();
});

function initEstufaSave() {
  const btnAddEstufa = document.getElementById("form-save-estufa");
  if (!btnAddEstufa) return;

  btnAddEstufa.addEventListener("click", async () => {
    const id = document.getElementById("e-id")?.value.trim();
    const nome = document.getElementById("e-nome")?.value.trim();
    const area = document.getElementById("e-area")?.value.trim();
    const obs = document.getElementById("e-obs")?.value.trim();

    if (!nome) {
      alert("Informe o nome da estufa");
      return;
    }

    const fd = new FormData();
    if (id) fd.append("id", id);
    fd.append("nome", nome);
    fd.append("area_m2", area);
    fd.append("obs", obs);

    if (typeof CadernoSalvar !== "undefined") {
      await CadernoSalvar.postFormData("salvar_estufa.php", fd, {
        redirect: false,
        onSuccess: () => location.reload(),
        onError: (d) => alert("Erro: " + (d?.err || d?.msg || "falha")),
      });
    }
  });

  // Ao abrir "Nova Estufa" limpa qualquer edição em andamento
  document.getElementById("estufa-add")?.addEventListener("click", () => {
    if (ignorarLimpezaEstufa) {
      ignorarLimpezaEstufa = false;
      return;
    }
    limparFormEstufa();
  });
}

let ignorarLimpezaEstufa = false;

function limparFormEstufa() {
  const inputId = document.getElementById("e-id");
  if (!inputId || !inputId.value) return;

  inputId.value = "";
  const inputNome = document.getElementById("e-nome");
  const inputArea = document.getElementById("e-area");
  const inputObs = document.getElementById("e-obs");
  if (inputNome) inputNome.value = "";
  if (inputArea) inputArea.value = "";
  if (inputObs) inputObs.value = "";

  const saveTxt = document.querySelector("#form-save-estufa .main-btn-text");
  if (saveTxt) saveTxt.textContent = "Salvar";
}

function editEstufa(btn) {
  let dados;
  try {
    dados = JSON.parse(btn.getAttribute("data-estufa"));
  } catch (err) {
    console.error("Dados da estufa inválidos:", err);
    return;
  }

  const addBtn = document.getElementById("estufa-add");
  const inputId = document.getElementById("e-id");

  // Se já está editando esta mesma estufa, o segundo clique fecha o formulário
  const jaEditandoEsta =
    addBtn?.classList.contains("active") &&
    String(inputId?.value || "") === String(dados.id);

  if (jaEditandoEsta) {
    addBtn.click(); // fecha a caixa e a limpeza é feita pelo listener do botão
    return;
  }

  // Fecha estufas abertas para exibir o formulário
  document.querySelectorAll(".item-estufa-box").forEach((div) => div.classList.add("d-none"));
  document.querySelectorAll("[id^='edit-estufa-']").forEach((b) => {
    b.textContent = "Selecionar";
    b.classList.remove("fechar");
  });

  const formNovaEstufa = document.getElementById("add-estufa");
  if (formNovaEstufa) formNovaEstufa.classList.remove("d-none");

  document.getElementById("e-id").value = dados.id || "";
  document.getElementById("e-nome").value = dados.nome || "";
  document.getElementById("e-area").value = dados.area_m2 || "";
  document.getElementById("e-obs").value = dados.obs || "";

  const saveTxt = document.querySelector("#form-save-estufa .main-btn-text");
  if (saveTxt) saveTxt.textContent = "Atualizar";

  // Abre a caixa do formulário (animação do main.js) sem disparar a limpeza
  if (addBtn && !addBtn.classList.contains("active")) {
    ignorarLimpezaEstufa = true;
    addBtn.click();
  }

  formNovaEstufa?.scrollIntoView({ behavior: "smooth", block: "start" });
}

/* ============================
   Exclusão com confirmação
   ============================ */

let hidroExclusao = null;

function abrirConfirmacaoExclusao(titulo, texto) {
  const overlayEl = document.getElementById("popup-overlay");
  const popupDelete = document.getElementById("popup-delete");
  if (!overlayEl || !popupDelete) {
    alert("Popup de confirmação não encontrado. Atualize a página (Ctrl+F5).");
    return false;
  }

  const titleEl = popupDelete.querySelector(".popup-title");
  const textEl = popupDelete.querySelector(".popup-text");
  if (titleEl) titleEl.textContent = titulo;
  if (textEl) textEl.textContent = texto;

  overlayEl.classList.remove("d-none");
  popupDelete.classList.remove("d-none");
  return true;
}

function mostrarFalhaHidro(titulo, texto) {
  const overlayEl = document.getElementById("popup-overlay");
  const popupFailedEl = document.getElementById("popup-failed");
  if (!overlayEl || !popupFailedEl) {
    alert(texto);
    return;
  }

  const titleEl = popupFailedEl.querySelector(".popup-title");
  const textEl = popupFailedEl.querySelector(".popup-text");
  if (titleEl) titleEl.textContent = titulo;
  if (textEl) textEl.textContent = texto;

  overlayEl.classList.remove("d-none");
  popupFailedEl.classList.remove("d-none");
}

function deleteEstufa(btn) {
  const id = btn.dataset.estufaId;
  const nome = btn.dataset.estufaNome || "esta estufa";
  if (!id) return;

  const aberto = abrirConfirmacaoExclusao(
    `Tem certeza que deseja excluir a estufa "${nome}"?`,
    "Todas as bancadas desta estufa também serão excluídas. Esta ação não poderá ser desfeita."
  );
  if (aberto) hidroExclusao = { tipo: "estufa", id };
}

function deleteBancada(btn) {
  const id = btn.dataset.bancadaId;
  const nome = btn.dataset.bancadaNome || "esta bancada";
  if (!id) return;

  const aberto = abrirConfirmacaoExclusao(
    `Tem certeza que deseja excluir a bancada "${nome}"?`,
    "Os registros de cultivos desta bancada também serão removidos. Esta ação não poderá ser desfeita."
  );
  if (aberto) hidroExclusao = { tipo: "bancada", id };
}

function initExcluirHidroponia() {
  const btnConfirm = document.getElementById("confirm-delete");
  if (!btnConfirm) return;

  btnConfirm.addEventListener("click", async () => {
    if (!hidroExclusao) return;

    const { tipo, id } = hidroExclusao;
    hidroExclusao = null;

    const url = tipo === "estufa" ? "/funcoes/remover_estufa.php" : "/funcoes/remover_bancada.php";
    const campo = tipo === "estufa" ? "estufa_id" : "bancada_id";

    try {
      const resp = await fetch(url, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `${campo}=${encodeURIComponent(id)}`,
      });
      const data = await resp.json();

      if (typeof closePopup === "function") closePopup();

      if (data.ok) {
        location.reload();
      } else {
        mostrarFalhaHidro(
          "Erro ao excluir",
          data.err || data.msg || `Não foi possível excluir a ${tipo}.`
        );
      }
    } catch (err) {
      console.error(err);
      if (typeof closePopup === "function") closePopup();
      mostrarFalhaHidro("Erro inesperado", "Falha de comunicação com o servidor.");
    }
  });
}

function initBancadaSave() {
  document.querySelectorAll("[id^='form-save-bancada-estufa-']").forEach((btn) => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();

      const btnEl = e.target.closest("button[id^='form-save-bancada-estufa-']");
      const idEstufa = btnEl.id.split("-").pop();
      const container = btnEl.closest(".item-add-box");
      if (!container) return;

      const nome =
        container.querySelector(`#b-nome-estufa-${idEstufa}`)?.value.trim() ||
        container.querySelector("[name='bnome']")?.value.trim() ||
        "";
      const obs =
        container.querySelector(`#b-obs-estufa-${idEstufa}`)?.value.trim() ||
        container.querySelector("[name='bobs']")?.value.trim() ||
        "";
      const barea = container.querySelector("[name='barea']")?.value.trim() || "";
      const barea_unidade = container.querySelector("[name='barea_unidade']")?.value || "m2";
      const produtos_json = document.getElementById(`produtos-json-estufa-${idEstufa}`)?.value.trim() || "";

      if (!nome) {
        alert("Informe o nome/número da bancada");
        return;
      }
      if (!produtos_json) {
        alert("Configure ao menos um produto cultivado");
        return;
      }

      const fd = new FormData();
      fd.append("estufa_id", idEstufa);
      fd.append("nome", nome);
      fd.append("obs", obs);
      fd.append("barea", barea);
      fd.append("barea_unidade", barea_unidade);
      fd.append("produtos_json", produtos_json);

      if (typeof CadernoSalvar !== "undefined") {
        await CadernoSalvar.postFormData("salvar_bancada.php", fd, {
          redirect: false,
          onSuccess: () => location.reload(),
          onError: (d) => alert(d?.err || d?.msg || "Erro ao salvar a bancada."),
        });
      }
    });
  });
}

function buildBancadaUrl(bancadaId) {
  return `${window.location.origin}/home/hidroponia?b=${bancadaId}`;
}

const qrBancadaState = {
  bancadaNome: "",
  estufaNome: "",
  url: "",
};

function sanitizeFilename(name) {
  return (
    String(name || "bancada")
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-zA-Z0-9-_]+/g, "-")
      .replace(/^-+|-+$/g, "")
      .slice(0, 60) || "bancada"
  );
}

function baixarImagemQrBancada() {
  const qrCanvas = document.getElementById("qr-bancada-canvas");
  if (!qrCanvas) return;

  const w = 420;
  const h = 540;
  const exportCanvas = document.createElement("canvas");
  exportCanvas.width = w;
  exportCanvas.height = h;
  const ctx = exportCanvas.getContext("2d");
  if (!ctx) return;

  ctx.fillStyle = "#ffffff";
  ctx.fillRect(0, 0, w, h);

  ctx.fillStyle = "#1a8a7a";
  ctx.fillRect(0, 0, w, 88);

  ctx.fillStyle = "#ffffff";
  ctx.textAlign = "center";
  ctx.font = "bold 26px Arial, Helvetica, sans-serif";
  ctx.fillText(qrBancadaState.bancadaNome || "Bancada", w / 2, 38);

  if (qrBancadaState.estufaNome) {
    ctx.font = "15px Arial, Helvetica, sans-serif";
    ctx.fillText(qrBancadaState.estufaNome, w / 2, 64);
  }

  const qrSize = 280;
  const x = (w - qrSize) / 2;
  ctx.drawImage(qrCanvas, x, 108, qrSize, qrSize);

  ctx.fillStyle = "#444444";
  ctx.font = "13px Arial, Helvetica, sans-serif";
  ctx.fillText("Caderno Frutag — Hidroponia", w / 2, h - 36);
  ctx.fillStyle = "#777777";
  ctx.font = "12px Arial, Helvetica, sans-serif";
  ctx.fillText("Escaneie para abrir no celular", w / 2, h - 16);

  const link = document.createElement("a");
  link.download = `QR-${sanitizeFilename(qrBancadaState.bancadaNome)}.png`;
  link.href = exportCanvas.toDataURL("image/png");
  link.click();
}

function renderQrOnCanvas(canvas, url) {
  return new Promise((resolve, reject) => {
    if (typeof QRCode !== "function") {
      reject(new Error("Biblioteca QR Code não carregou"));
      return;
    }

    const wrap = document.createElement("div");
    wrap.style.cssText = "position:fixed;left:-9999px;top:0;width:1px;height:1px;overflow:hidden";
    document.body.appendChild(wrap);

    try {
      const level = QRCode.CorrectLevel?.M ?? QRCode.CorrectLevel?.H;
      // eslint-disable-next-line no-new
      new QRCode(wrap, {
        text: url,
        width: 240,
        height: 240,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: level,
      });

      const copyToTarget = () => {
        const srcCanvas = wrap.querySelector("canvas");
        const img = wrap.querySelector("img");
        const ctx = canvas.getContext("2d");
        if (!ctx) {
          wrap.remove();
          reject(new Error("Canvas indisponível"));
          return;
        }

        canvas.width = 240;
        canvas.height = 240;
        ctx.fillStyle = "#ffffff";
        ctx.fillRect(0, 0, 240, 240);

        const done = (source) => {
          ctx.drawImage(source, 0, 0, 240, 240);
          wrap.remove();
          resolve();
        };

        if (srcCanvas) {
          done(srcCanvas);
        } else if (img) {
          if (img.complete && img.naturalWidth) done(img);
          else img.onload = () => done(img);
        } else {
          wrap.remove();
          reject(new Error("QR Code não foi gerado"));
        }
      };

      setTimeout(copyToTarget, 80);
    } catch (err) {
      wrap.remove();
      reject(err);
    }
  });
}

function initQrBancada() {
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".btn-qr-bancada");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    const bancadaId = btn.dataset.bancadaId;
    const bancadaNome = btn.dataset.bancadaNome || "Bancada";
    const estufaNome = btn.dataset.estufaNome || "";
    if (!bancadaId) return;

    const overlay = document.getElementById("popup-qr-bancada-overlay");
    const canvas = document.getElementById("qr-bancada-canvas");
    const nomeEl = document.getElementById("popup-qr-bancada-nome");
    const estufaEl = document.getElementById("popup-qr-bancada-estufa");
    const urlEl = document.getElementById("qr-bancada-url");

    if (!overlay || !canvas) {
      alert("Popup de QR Code não encontrado. Atualize a página (Ctrl+F5).");
      return;
    }

    if (typeof QRCode !== "function") {
      alert("Biblioteca de QR Code não carregou. Verifique sua conexão e atualize a página.");
      return;
    }

    try {
      const resp = await fetch(`/funcoes/qr_bancada.php?bancada_id=${encodeURIComponent(bancadaId)}`, {
        credentials: "same-origin",
      });
      const data = await resp.json();
      if (!data.ok) {
        alert(data.err || "Erro ao gerar link.");
        return;
      }

      qrBancadaState.url = data.url || buildBancadaUrl(bancadaId);
      qrBancadaState.bancadaNome = data.bancada_nome || bancadaNome;
      qrBancadaState.estufaNome = data.estufa_nome || estufaNome;

      if (nomeEl) nomeEl.textContent = qrBancadaState.bancadaNome;
      if (estufaEl) estufaEl.textContent = qrBancadaState.estufaNome;
      if (urlEl) urlEl.textContent = qrBancadaState.url;

      await renderQrOnCanvas(canvas, qrBancadaState.url);
      overlay.classList.remove("d-none");
    } catch (err) {
      console.error(err);
      alert("Falha ao gerar QR Code. Tente novamente.");
    }
  });

  document.getElementById("btn-qr-bancada-fechar")?.addEventListener("click", () => {
    document.getElementById("popup-qr-bancada-overlay")?.classList.add("d-none");
  });

  document.getElementById("popup-qr-bancada-overlay")?.addEventListener("click", (e) => {
    if (e.target.id === "popup-qr-bancada-overlay") {
      e.currentTarget.classList.add("d-none");
    }
  });

  document.getElementById("btn-qr-bancada-baixar")?.addEventListener("click", () => {
    baixarImagemQrBancada();
  });
}

function abrirBancadaPorQr() {
  const params = new URLSearchParams(window.location.search);
  const bancadaId = params.get("b");
  if (!bancadaId) return;

  const btn = document.querySelector(`.item-bancada[data-bancada-id="${bancadaId}"]`);
  if (!btn) return;

  const estufaId = parseInt(btn.dataset.estufaId, 10);
  const nome = btn.dataset.bancadaNome;
  if (!estufaId || !nome) return;

  selectEstufa(estufaId);
  selectBancada(nome, estufaId);

  if (window.history?.replaceState) {
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}

function selectEstufa(idEstufa) {
  const box = document.getElementById(`estufa-${idEstufa}-box`);
  const btn = document.getElementById(`edit-estufa-${idEstufa}`);
  const formNovaEstufa = document.getElementById("add-estufa");

  if (!box || !btn) return;

  const isOpen = !box.classList.contains("d-none");

  document.querySelectorAll(".item-estufa-box").forEach((div) => div.classList.add("d-none"));
  document.querySelectorAll("[id^='edit-estufa-']").forEach((b) => {
    b.textContent = "Selecionar";
    b.classList.remove("fechar");
  });

  if (isOpen) {
    box.classList.add("d-none");
    btn.textContent = "Selecionar";
    btn.classList.remove("fechar");
  } else {
    box.classList.remove("d-none");
    btn.textContent = "Fechar";
    btn.classList.add("fechar");
  }

  const algumaAberta = Array.from(document.querySelectorAll(".item-estufa-box")).some(
    (div) => !div.classList.contains("d-none")
  );

  if (formNovaEstufa) {
    if (algumaAberta) formNovaEstufa.classList.add("d-none");
    else formNovaEstufa.classList.remove("d-none");
  }
}

function destacarBancadaSelecionada(nomeBancada, idEstufa) {
  document.querySelectorAll(".item-bancada").forEach((btn) => btn.classList.remove("bancada-selecionada"));

  const btnAtual =
    document.getElementById(`item-bancada-${nomeBancada}-estufa-${idEstufa}`) ||
    document.querySelector(`.item-bancada[data-estufa-id="${idEstufa}"][data-bancada-nome="${nomeBancada}"]`);

  if (btnAtual) btnAtual.classList.add("bancada-selecionada");
}

function selectBancada(nomeBancada, idEstufa) {
  document.querySelectorAll(".item-bancada-content").forEach((div) => div.classList.add("d-none"));

  document.querySelectorAll(".item-estufa-box").forEach((div) => {
    if (!div.id.includes(`estufa-${idEstufa}-box`)) div.classList.add("d-none");
  });

  const formNovaEstufa = document.getElementById("add-estufa");
  if (formNovaEstufa) formNovaEstufa.classList.add("d-none");

  const formNovaBancada = document.getElementById(`add-bancada-estufa-${idEstufa}`);
  if (formNovaBancada) formNovaBancada.classList.add("d-none");

  const estufaBox = document.getElementById(`estufa-${idEstufa}-box`);
  if (estufaBox) estufaBox.classList.remove("d-none");

  const btnBancada =
    document.getElementById(`item-bancada-${nomeBancada}-estufa-${idEstufa}`) ||
    document.querySelector(`.item-bancada[data-estufa-id="${idEstufa}"][data-bancada-nome="${nomeBancada}"]`);

  let box = document.getElementById(`item-bancada-${nomeBancada}-content-estufa-${idEstufa}`);
  if (!box && btnBancada?.nextElementSibling?.classList.contains("item-bancada-content")) {
    box = btnBancada.nextElementSibling;
  }
  if (box) box.classList.remove("d-none");

  const btn = document.getElementById(`edit-estufa-${idEstufa}`);
  if (btn) {
    btn.textContent = "Fechar";
    btn.classList.add("fechar");
  }

  destacarBancadaSelecionada(nomeBancada, idEstufa);
}

function voltarEstufa(idEstufa) {
  document.querySelectorAll(".item-bancada-content").forEach((div) => div.classList.add("d-none"));
  document.querySelectorAll(".item-bancada").forEach((btn) => btn.classList.remove("bancada-selecionada"));

  const box = document.getElementById(`estufa-${idEstufa}-box`);
  if (box) box.classList.remove("d-none");

  const btn = document.getElementById(`edit-estufa-${idEstufa}`);
  if (btn) {
    btn.textContent = "Fechar";
    btn.classList.add("fechar");
  }

  const formNovaEstufa = document.getElementById("add-estufa");
  if (formNovaEstufa) formNovaEstufa.classList.add("d-none");

  const formNovaBancada = document.getElementById(`add-bancada-estufa-${idEstufa}`);
  if (formNovaBancada) formNovaBancada.classList.remove("d-none");
}

async function carregarProdutos(selectAlvo = null) {
  try {
    const resp = await fetch("/funcoes/buscar_produtos.php", { credentials: "same-origin" });
    const data = await resp.json();
    if (!Array.isArray(data)) return;

    const selects = selectAlvo ? [selectAlvo] : document.querySelectorAll(".produto-select");
    selects.forEach((sel) => {
      const valorAtual = sel.value;
      sel.innerHTML = '<option value="">Selecione o produto</option>';
      data.forEach((p) => {
        const opt = document.createElement("option");
        opt.value = p.id;
        opt.textContent = p.nome;
        if (String(p.id) === String(valorAtual)) opt.selected = true;
        sel.appendChild(opt);
      });
    });
  } catch (err) {
    console.error("Erro ao carregar produtos:", err);
  }
}

window.selectEstufa = selectEstufa;
window.selectBancada = selectBancada;
window.voltarEstufa = voltarEstufa;
window.editEstufa = editEstufa;
window.deleteEstufa = deleteEstufa;
window.deleteBancada = deleteBancada;
