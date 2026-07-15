/**
 * FRUTIBANK.JS
 * ------------
 * Painel do Frutibank: chave PIX do recebedor, clientes de cobrança
 * (CPF/CNPJ com busca na Receita Federal) e geração de cobranças PIX.
 * Usa os popups padrão do sistema (include/popups.php + js/popups.js).
 */

document.addEventListener("DOMContentLoaded", () => {
  const api = "../funcoes/frutibank/api.php";

  const formConfig = document.getElementById("form-config");
  const formCliente = document.getElementById("form-cliente");
  const formCobranca = document.getElementById("form-cobranca");
  const tbodyClientes = document.querySelector("#tabela-fb-clientes tbody");
  const tbodyCobrancas = document.querySelector("#tabela-fb-cobrancas tbody");
  const selectCliente = document.getElementById("fb-cob-cliente");
  const chipConfig = document.getElementById("fb-chip-config");
  const chipClientes = document.getElementById("fb-chip-clientes");
  const chipCobrancas = document.getElementById("fb-chip-cobrancas");

  /* ---------------- Popups padrão do sistema ---------------- */

  const fbOverlay = document.getElementById("popup-overlay");

  function fbPopup(titulo, texto = "", sucesso = true) {
    const box = document.getElementById(sucesso ? "popup-success" : "popup-failed");
    if (!fbOverlay || !box || typeof closePopup !== "function") {
      alert(titulo + (texto ? "\n" + texto : ""));
      return;
    }
    const t = box.querySelector(".popup-title");
    if (t) t.textContent = titulo;

    let p = box.querySelector(".popup-text");
    if (!p && t) {
      p = document.createElement("p");
      p.className = "popup-text";
      t.insertAdjacentElement("afterend", p);
    }
    if (p) {
      p.textContent = texto;
      p.style.display = texto ? "" : "none";
    }

    fbOverlay.classList.remove("d-none");
    box.classList.remove("d-none");
  }

  function fbConfirm(titulo, texto, btnLabel = "Confirmar", btnClasse = "fundo-vermelho") {
    return new Promise((resolve) => {
      const box = document.getElementById("popup-delete");
      if (!fbOverlay || !box) {
        resolve(confirm(`${titulo}\n${texto}`));
        return;
      }
      box.querySelector(".popup-title").textContent = titulo;
      box.querySelector(".popup-text").textContent = texto;

      const btnConfirmar = box.querySelector("#confirm-delete");
      const btnCancelar = box.querySelector(".popup-btn:not(#confirm-delete)");
      btnConfirmar.textContent = btnLabel;
      btnConfirmar.className = `popup-btn ${btnClasse}`;

      const onConfirmar = () => {
        limpar();
        closePopup();
        resolve(true);
      };
      const onCancelar = () => {
        limpar();
        resolve(false);
      };
      function limpar() {
        btnConfirmar.removeEventListener("click", onConfirmar);
        btnCancelar.removeEventListener("click", onCancelar);
      }

      btnConfirmar.addEventListener("click", onConfirmar);
      btnCancelar.addEventListener("click", onCancelar);

      fbOverlay.classList.remove("d-none");
      box.classList.remove("d-none");
    });
  }

  /* ---------------- Abas ---------------- */

  const tabs = document.querySelectorAll("#fb-tabs .fb-tab");
  const panels = document.querySelectorAll(".fb-panel");
  let tabEscolhidaPeloUsuario = false;

  function abrirTab(nome, peloUsuario = false) {
    if (peloUsuario) tabEscolhidaPeloUsuario = true;
    tabs.forEach((t) => t.classList.toggle("active", t.dataset.tab === nome));
    panels.forEach((p) => p.classList.toggle("active", p.dataset.panel === nome));
    if (peloUsuario) history.replaceState(null, "", `#${nome}`);
  }

  tabs.forEach((t) => t.addEventListener("click", () => abrirTab(t.dataset.tab, true)));

  const hashInicial = (location.hash || "").replace("#", "");
  const tabInicial = ["chave", "clientes", "cobrancas"].includes(hashInicial) ? hashInicial : "chave";
  abrirTab(tabInicial, hashInicial !== "");

  /* ---------------- Utilidades ---------------- */

  function escapeHtml(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function soDigitos(s) {
    return String(s || "").replace(/\D/g, "");
  }

  function fmtDoc(doc) {
    const d = soDigitos(doc);
    if (d.length === 11) return d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
    if (d.length === 14) return d.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
    return doc || "—";
  }

  function mascaraDoc(valor) {
    const d = soDigitos(valor).slice(0, 14);
    if (d.length <= 11) {
      return d
        .replace(/(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})\.(\d{3})(\d)/, "$1.$2.$3")
        .replace(/\.(\d{3})(\d{1,2})$/, ".$1-$2");
    }
    return d
      .replace(/^(\d{2})(\d)/, "$1.$2")
      .replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3")
      .replace(/\.(\d{3})(\d)/, ".$1/$2")
      .replace(/(\d{4})(\d{1,2})$/, "$1-$2");
  }

  function mascaraTelefone(valor) {
    const d = soDigitos(valor).slice(0, 11);
    if (d.length <= 2) return d;
    if (d.length <= 7) return `(${d.slice(0, 2)}) ${d.slice(2)}`;
    return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
  }

  function fmtValor(v) {
    return Number(v).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
  }

  function fmtData(iso) {
    if (!iso) return "—";
    const d = new Date(String(iso).replace(" ", "T"));
    return Number.isNaN(d.getTime()) ? iso : d.toLocaleDateString("pt-BR");
  }

  async function apiCall(acao, opts = {}) {
    const method = opts.method || "GET";
    let url = `${api}?acao=${encodeURIComponent(acao)}`;
    const init = { method, credentials: "same-origin" };

    if (method === "POST") {
      const fd = opts.body instanceof FormData ? opts.body : new FormData();
      if (!(opts.body instanceof FormData)) {
        Object.entries(opts.body || {}).forEach(([k, v]) => fd.append(k, v));
      }
      fd.append("acao", acao);
      init.body = fd;
    } else if (opts.params) {
      Object.entries(opts.params).forEach(([k, v]) => {
        if (v !== "" && v != null) url += `&${encodeURIComponent(k)}=${encodeURIComponent(v)}`;
      });
    }

    const r = await fetch(url, init);
    const data = await r.json();
    if (!data.ok) throw new Error(data.msg || "Erro na requisição");
    return data;
  }

  /* ---------------- Chave PIX ---------------- */

  const inputChave = document.getElementById("fb-chave");
  const hintChave = document.getElementById("fb-chave-hint");
  const selTipo = document.getElementById("fb-tipo");
  const inputNomeRec = document.getElementById("fb-nome");
  const inputCidade = document.getElementById("fb-cidade");

  const tiposChave = {
    cpf: { placeholder: "000.000.000-00", hint: "Digite o CPF cadastrado como chave no seu banco.", inputmode: "numeric" },
    cnpj: { placeholder: "00.000.000/0000-00", hint: "Digite o CNPJ cadastrado como chave no seu banco.", inputmode: "numeric" },
    email: { placeholder: "voce@email.com", hint: "Digite o e-mail cadastrado como chave no seu banco.", inputmode: "email" },
    telefone: { placeholder: "(00) 90000-0000", hint: "Digite o celular com DDD. Salvamos no formato +55 exigido pelo PIX.", inputmode: "tel" },
    aleatoria: { placeholder: "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx", hint: "Cole a chave aleatória gerada pelo seu banco (32 caracteres).", inputmode: "text" },
  };

  function aplicarTipoChave(manterValor = false) {
    const cfg = tiposChave[selTipo.value] || tiposChave.aleatoria;
    inputChave.placeholder = cfg.placeholder;
    inputChave.setAttribute("inputmode", cfg.inputmode);
    if (hintChave) hintChave.textContent = cfg.hint;
    if (!manterValor) inputChave.value = "";
    atualizarPreview();
  }

  function formatarChaveDigitada() {
    const tipo = selTipo.value;
    if (tipo === "cpf" || tipo === "cnpj") {
      inputChave.value = mascaraDoc(inputChave.value);
    } else if (tipo === "telefone") {
      // preserva valores já salvos no formato +55...
      if (!inputChave.value.startsWith("+")) inputChave.value = mascaraTelefone(inputChave.value);
    }
    atualizarPreview();
  }

  function chaveNormalizada() {
    const tipo = selTipo.value;
    const bruto = inputChave.value.trim();
    if (tipo === "cpf" || tipo === "cnpj") return soDigitos(bruto);
    if (tipo === "telefone") {
      if (bruto.startsWith("+")) return bruto;
      const d = soDigitos(bruto);
      return d ? `+55${d}` : "";
    }
    if (tipo === "email") return bruto.toLowerCase();
    return bruto;
  }

  function validarChave() {
    const tipo = selTipo.value;
    const chave = chaveNormalizada();
    if (tipo === "cpf" && chave.length !== 11) return "O CPF da chave deve ter 11 dígitos.";
    if (tipo === "cnpj" && chave.length !== 14) return "O CNPJ da chave deve ter 14 dígitos.";
    if (tipo === "telefone" && !/^\+55\d{10,11}$/.test(chave)) return "Informe um celular válido com DDD.";
    if (tipo === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(chave)) return "Informe um e-mail válido.";
    if (tipo === "aleatoria" && chave.length < 30) return "A chave aleatória parece incompleta. Copie e cole a chave completa do seu banco.";
    return null;
  }

  function atualizarPreview() {
    const prevNome = document.getElementById("fb-prev-nome");
    const prevCidade = document.getElementById("fb-prev-cidade");
    const prevChave = document.getElementById("fb-prev-chave");
    if (prevNome) prevNome.textContent = inputNomeRec.value.trim().toUpperCase() || "—";
    if (prevCidade) prevCidade.textContent = inputCidade.value.trim().toUpperCase() || "—";
    if (prevChave) prevChave.textContent = chaveNormalizada() || "—";
  }

  selTipo?.addEventListener("change", () => aplicarTipoChave(false));
  inputChave?.addEventListener("input", formatarChaveDigitada);
  inputNomeRec?.addEventListener("input", atualizarPreview);
  inputCidade?.addEventListener("input", atualizarPreview);

  async function carregarConfig() {
    const data = await apiCall("get_config");
    const c = data.config;
    if (chipConfig) chipConfig.textContent = c ? "OK" : "Pendente";
    if (!c) {
      aplicarTipoChave(false);
      return;
    }
    selTipo.value = c.tipo_chave;
    aplicarTipoChave(true);
    inputChave.value = c.tipo_chave === "cpf" || c.tipo_chave === "cnpj" ? mascaraDoc(c.chave_pix) : c.chave_pix;
    inputNomeRec.value = c.nome_recebedor;
    inputCidade.value = c.cidade;
    atualizarPreview();
    // chave já configurada: abre direto em Cobranças (se o usuário não escolheu outra aba)
    if (!tabEscolhidaPeloUsuario) abrirTab("cobrancas");
  }

  formConfig?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const erro = validarChave();
    if (erro) {
      fbPopup("Chave PIX inválida", erro, false);
      return;
    }
    const fd = new FormData(formConfig);
    fd.set("chave_pix", chaveNormalizada());
    try {
      await apiCall("salvar_config", { method: "POST", body: fd });
      fbPopup("Chave PIX salva com sucesso!", "Agora você já pode gerar cobranças para os seus clientes.");
      await carregarConfig();
    } catch (err) {
      fbPopup("Não foi possível salvar", err.message, false);
    }
  });

  /* ---------------- Clientes ---------------- */

  const inputCliDoc = document.getElementById("fb-cli-doc");
  const inputCliNome = document.getElementById("fb-cli-nome");
  const btnReceita = document.getElementById("fb-btn-receita");
  const infoReceita = document.getElementById("fb-receita-info");
  const infoReceitaPadrao = infoReceita ? infoReceita.textContent : "";

  inputCliDoc?.addEventListener("input", () => {
    inputCliDoc.value = mascaraDoc(inputCliDoc.value);
    const d = soDigitos(inputCliDoc.value);
    if (btnReceita) btnReceita.disabled = d.length !== 14;
    if (infoReceita && d.length === 0) infoReceita.textContent = infoReceitaPadrao;
  });

  btnReceita?.addEventListener("click", async () => {
    const cnpj = soDigitos(inputCliDoc.value);
    if (cnpj.length !== 14) return;
    btnReceita.disabled = true;
    const rotulo = btnReceita.textContent;
    btnReceita.textContent = "Buscando...";
    try {
      const data = await apiCall("consultar_cnpj", { params: { cnpj } });
      const d = data.dados;
      inputCliNome.value = d.razao_social || d.nome_fantasia || "";
      if (infoReceita) {
        const partes = [];
        if (d.nome_fantasia) partes.push(`Fantasia: ${d.nome_fantasia}`);
        if (d.situacao) partes.push(`Situação: ${d.situacao}`);
        if (d.municipio) partes.push(`${d.municipio}${d.uf ? "/" + d.uf : ""}`);
        infoReceita.textContent = partes.join(" · ") || "Dados encontrados na Receita Federal.";
      }
      if (d.situacao && d.situacao.toUpperCase() !== "ATIVA") {
        fbPopup("Atenção com este CNPJ", `A situação cadastral na Receita é "${d.situacao}". Confira antes de cobrar.`, false);
      }
    } catch (err) {
      fbPopup("Consulta não concluída", err.message, false);
    } finally {
      btnReceita.textContent = rotulo;
      btnReceita.disabled = soDigitos(inputCliDoc.value).length !== 14;
    }
  });

  async function carregarClientes() {
    const data = await apiCall("listar_clientes");
    const clientes = data.clientes;
    if (chipClientes) chipClientes.textContent = clientes.length;

    tbodyClientes.innerHTML = clientes.length
      ? clientes
          .map(
            (c) => `<tr data-cliente-id="${c.id}">
          <td><span class="au-nome">${escapeHtml(c.nome)}</span></td>
          <td>${fmtDoc(c.cpf_cnpj)}</td>
          <td>${c.total_cobrancas}</td>
          <td class="au-acoes">
            <button type="button" class="au-btn au-btn-acessar" data-cobrar>Gerar cobrança</button>
            ${Number(c.total_cobrancas) === 0 ? `<button type="button" class="au-btn au-btn-excluir" data-excluir>Excluir</button>` : ""}
          </td>
        </tr>`
          )
          .join("")
      : `<tr class="au-vazio"><td colspan="4">Nenhum cliente cadastrado ainda.</td></tr>`;

    // popula o select de cobrança
    const atual = selectCliente.value;
    selectCliente.innerHTML =
      `<option value="">Selecione um cliente...</option>` +
      clientes.map((c) => `<option value="${c.id}">${escapeHtml(c.nome)} — ${fmtDoc(c.cpf_cnpj)}</option>`).join("");
    if (atual) selectCliente.value = atual;
  }

  formCliente?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(formCliente);
    fd.set("cpf_cnpj", soDigitos(inputCliDoc.value));
    try {
      await apiCall("salvar_cliente", { method: "POST", body: fd });
      fbPopup("Cliente cadastrado!", "Agora você já pode gerar cobranças para ele.");
      formCliente.reset();
      if (infoReceita) infoReceita.textContent = infoReceitaPadrao;
      if (btnReceita) btnReceita.disabled = true;
      await carregarClientes();
    } catch (err) {
      fbPopup("Não foi possível cadastrar", err.message, false);
    }
  });

  tbodyClientes?.addEventListener("click", async (e) => {
    const tr = e.target.closest("tr[data-cliente-id]");
    if (!tr) return;

    if (e.target.closest("[data-cobrar]")) {
      abrirTab("cobrancas", true);
      selectCliente.value = tr.dataset.clienteId;
      document.getElementById("fb-cob-valor")?.focus();
      formCobranca?.scrollIntoView({ behavior: "smooth", block: "center" });
      return;
    }

    if (e.target.closest("[data-excluir]")) {
      const nome = tr.querySelector(".au-nome")?.textContent || "este cliente";
      const ok = await fbConfirm("Excluir cliente?", `${nome} será removido da sua lista. Esta ação não poderá ser desfeita.`, "Excluir");
      if (!ok) return;
      try {
        await apiCall("excluir_cliente", { method: "POST", body: { cliente_id: tr.dataset.clienteId } });
        await carregarClientes();
      } catch (err) {
        fbPopup("Não foi possível excluir", err.message, false);
      }
    }
  });

  /* ---------------- Cobranças ---------------- */

  const statusLabels = { pendente: "Pendente", pago: "Pago", cancelada: "Cancelada" };

  async function carregarCobrancas() {
    const data = await apiCall("listar_cobrancas");
    const cobrancas = data.cobrancas;
    if (chipCobrancas) chipCobrancas.textContent = cobrancas.length;

    tbodyCobrancas.innerHTML = cobrancas.length
      ? cobrancas
          .map((fc) => {
            const options = Object.entries(statusLabels)
              .map(([v, l]) => `<option value="${v}" ${v === fc.status ? "selected" : ""}>${l}</option>`)
              .join("");
            return `<tr data-cobranca-id="${fc.id}">
          <td>${fmtData(fc.criado_em)}</td>
          <td><span class="au-nome">${escapeHtml(fc.cliente_nome)}</span><small class="au-sub">${fmtDoc(fc.cliente_doc)}</small></td>
          <td><strong>${fmtValor(fc.valor)}</strong></td>
          <td>${fc.vencimento ? fmtData(fc.vencimento) : "—"}</td>
          <td><select class="au-select fb-status fb-status-${fc.status}" data-status-select>${options}</select></td>
          <td class="au-acoes">
            <a class="au-btn au-btn-acessar" href="/home/frutibank_cobranca?id=${fc.id}" target="_blank" rel="noopener">Ver / Imprimir</a>
          </td>
        </tr>`;
          })
          .join("")
      : `<tr class="au-vazio"><td colspan="6">Nenhuma cobrança gerada ainda.</td></tr>`;
  }

  formCobranca?.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!selectCliente.value) {
      fbPopup("Selecione um cliente", "Escolha para quem é a cobrança antes de gerar.", false);
      return;
    }
    try {
      const data = await apiCall("criar_cobranca", { method: "POST", body: new FormData(formCobranca) });
      formCobranca.reset();
      await Promise.all([carregarCobrancas(), carregarClientes()]);
      if (data.url) window.open(data.url, "_blank", "noopener");
    } catch (err) {
      fbPopup("Não foi possível gerar a cobrança", err.message, false);
    }
  });

  tbodyCobrancas?.addEventListener("change", async (e) => {
    const sel = e.target.closest("[data-status-select]");
    if (!sel) return;
    const tr = sel.closest("tr[data-cobranca-id]");
    try {
      await apiCall("atualizar_status", {
        method: "POST",
        body: { cobranca_id: tr.dataset.cobrancaId, status: sel.value },
      });
      sel.className = `au-select fb-status fb-status-${sel.value}`;
    } catch (err) {
      fbPopup("Não foi possível atualizar", err.message, false);
      await carregarCobrancas();
    }
  });

  /* ---------------- Inicialização ---------------- */

  Promise.all([carregarConfig(), carregarClientes(), carregarCobrancas()]).catch((err) =>
    fbPopup("Erro ao carregar o Frutibank", err.message, false)
  );
});
