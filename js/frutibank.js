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

  const tabsNav = document.getElementById("fb-tabs");
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
  const selUf = document.getElementById("fb-uf");
  const selCidade = document.getElementById("fb-cidade");

  /* --- Estados e cidades (IBGE) --- */

  const ibge = "https://servicodados.ibge.gov.br/api/v1/localidades";

  async function carregarEstadosFb() {
    if (!selUf || selUf.options.length > 1) return;
    try {
      const r = await fetch(`${ibge}/estados?orderBy=nome`);
      const estados = await r.json();
      estados.forEach((e) => {
        const opt = document.createElement("option");
        opt.value = e.sigla;
        opt.textContent = `${e.sigla} — ${e.nome}`;
        selUf.appendChild(opt);
      });
    } catch {
      // sem internet: mantém o select vazio; o usuário pode tentar de novo depois
    }
  }

  async function carregarCidadesFb(uf, selecionada = "") {
    if (!selCidade) return;
    selCidade.innerHTML = `<option value="">${uf ? "Carregando..." : "Selecione o estado primeiro..."}</option>`;
    if (!uf) return;
    try {
      const r = await fetch(`${ibge}/estados/${uf}/municipios?orderBy=nome`);
      const cidades = await r.json();
      selCidade.innerHTML = `<option value="">Selecione a cidade...</option>`;
      cidades.forEach((c) => {
        const opt = document.createElement("option");
        opt.value = c.nome;
        opt.textContent = c.nome;
        selCidade.appendChild(opt);
      });
      if (selecionada) selCidade.value = selecionada;
    } catch {
      selCidade.innerHTML = `<option value="">Erro ao carregar. Selecione o estado de novo.</option>`;
    }
    atualizarPreview();
  }

  selUf?.addEventListener("change", () => carregarCidadesFb(selUf.value));
  selCidade?.addEventListener("change", atualizarPreview);

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

  // Reproduz o tratamento do BR Code: sem acentos e com limite de caracteres
  function textoEmv(s, max) {
    return String(s || "")
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .trim()
      .toUpperCase()
      .slice(0, max);
  }

  function atualizarPreview() {
    const prevNome = document.getElementById("fb-prev-nome");
    const prevCidade = document.getElementById("fb-prev-cidade");
    const prevChave = document.getElementById("fb-prev-chave");
    if (prevNome) prevNome.textContent = textoEmv(inputNomeRec.value, 25) || "—";
    if (prevCidade) prevCidade.textContent = textoEmv(selCidade?.value, 15) || "—";
    if (prevChave) prevChave.textContent = chaveNormalizada() || "—";
  }

  selTipo?.addEventListener("change", () => aplicarTipoChave(false));
  inputChave?.addEventListener("input", formatarChaveDigitada);
  inputNomeRec?.addEventListener("input", atualizarPreview);

  /* --- Modo visualização x edição da chave --- */

  const configView = document.getElementById("fb-config-view");
  const chaveEditor = document.getElementById("fb-chave-editor");
  const btnEditarConfig = document.getElementById("fb-btn-editar-config");
  const btnCancelarConfig = document.getElementById("fb-btn-cancelar-config");
  const tipoLabels = { cpf: "CPF", cnpj: "CNPJ", email: "E-mail", telefone: "Telefone", aleatoria: "Aleatória" };
  let configAtual = null;

  function chaveExibicao(c) {
    if (c.tipo_chave === "cpf" || c.tipo_chave === "cnpj") return mascaraDoc(c.chave_pix);
    return c.chave_pix;
  }

  async function preencherFormConfig(c) {
    selTipo.value = c.tipo_chave;
    aplicarTipoChave(true);
    inputChave.value = chaveExibicao(c);
    inputNomeRec.value = c.nome_recebedor;
    await carregarEstadosFb();
    if (selUf) selUf.value = c.uf || "";
    await carregarCidadesFb(c.uf || "", c.cidade || "");
    atualizarPreview();
  }

  function mostrarConfigView() {
    if (!configAtual || !configView) return;
    const cidadeUf = [configAtual.cidade, configAtual.uf].filter(Boolean).join(" / ");
    document.getElementById("fb-view-tipo").textContent = tipoLabels[configAtual.tipo_chave] || configAtual.tipo_chave;
    document.getElementById("fb-view-chave").textContent = chaveExibicao(configAtual);
    document.getElementById("fb-view-nome").textContent = String(configAtual.nome_recebedor || "").toUpperCase();
    document.getElementById("fb-view-cidade").textContent = cidadeUf.toUpperCase() || "—";
    configView.classList.remove("d-none");
    chaveEditor?.classList.add("d-none");
  }

  function mostrarConfigForm() {
    configView?.classList.add("d-none");
    chaveEditor?.classList.remove("d-none");
    // só dá para cancelar a edição se já existe uma chave salva
    btnCancelarConfig?.classList.toggle("d-none", !configAtual);
  }

  btnEditarConfig?.addEventListener("click", () => {
    mostrarConfigForm();
    inputChave?.focus();
  });

  btnCancelarConfig?.addEventListener("click", async () => {
    if (configAtual) await preencherFormConfig(configAtual);
    mostrarConfigView();
  });

  // Com a chave cadastrada, a ordem vira: Cobranças, Clientes, Chave PIX
  function reordenarTabsComChave() {
    const tabCobrancas = tabsNav?.querySelector('.fb-tab[data-tab="cobrancas"]');
    const tabChave = tabsNav?.querySelector('.fb-tab[data-tab="chave"]');
    if (tabCobrancas && tabsNav.firstElementChild !== tabCobrancas) tabsNav.prepend(tabCobrancas);
    if (tabChave && tabsNav.lastElementChild !== tabChave) tabsNav.appendChild(tabChave);
  }

  async function carregarConfig() {
    const data = await apiCall("get_config");
    const c = data.config;
    configAtual = c || null;
    if (chipConfig) chipConfig.textContent = c ? "OK" : "Pendente";
    if (!c) {
      aplicarTipoChave(false);
      await carregarEstadosFb();
      mostrarConfigForm();
      return;
    }
    await preencherFormConfig(c);
    mostrarConfigView();
    // chave já configurada: Cobranças vai para o início e abre por padrão
    reordenarTabsComChave();
    if (!tabEscolhidaPeloUsuario) abrirTab("cobrancas");
  }

  formConfig?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const erro = validarChave();
    if (erro) {
      fbPopup("Chave PIX inválida", erro, false);
      return;
    }
    if (!selUf?.value || !selCidade?.value) {
      fbPopup("Falta o estado e a cidade", "Selecione o estado e a cidade do recebedor (obrigatórios no padrão PIX).", false);
      return;
    }
    const fd = new FormData(formConfig);
    fd.set("chave_pix", chaveNormalizada());
    try {
      await apiCall("salvar_config", { method: "POST", body: fd });
      const data = await apiCall("get_config");
      configAtual = data.config || null;
      if (chipConfig) chipConfig.textContent = configAtual ? "OK" : "Pendente";
      if (configAtual) {
        await preencherFormConfig(configAtual);
        mostrarConfigView();
        reordenarTabsComChave();
      }
      fbPopup("Chave PIX salva com sucesso!", "Agora você já pode gerar cobranças para os seus clientes.");
    } catch (err) {
      fbPopup("Não foi possível salvar", err.message, false);
    }
  });

  /* ---------------- Clientes ---------------- */

  const inputCliDoc = document.getElementById("fb-cli-doc");
  const inputCliNome = document.getElementById("fb-cli-nome");
  const inputCliTel = document.getElementById("fb-cli-tel");
  const btnReceita = document.getElementById("fb-btn-receita");
  const infoReceita = document.getElementById("fb-receita-info");
  const infoReceitaPadrao = infoReceita ? infoReceita.textContent : "";

  inputCliDoc?.addEventListener("input", () => {
    inputCliDoc.value = mascaraDoc(inputCliDoc.value);
    const d = soDigitos(inputCliDoc.value);
    if (btnReceita) btnReceita.disabled = d.length !== 14;
    if (infoReceita && d.length === 0) infoReceita.textContent = infoReceitaPadrao;
  });

  inputCliTel?.addEventListener("input", () => {
    inputCliTel.value = mascaraTelefone(inputCliTel.value);
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
    fd.set("telefone", soDigitos(inputCliTel?.value || ""));
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
  const cobrancasPorId = new Map();

  function abrirWhatsappCobranca(fc) {
    const valor = fmtValor(fc.valor);
    const linkPublico = `${location.origin}/home/frutibank_cobranca?t=${fc.token}`;
    const partes = [
      `Olá, ${fc.cliente_nome}!`,
      "",
      `Segue a cobrança PIX de ${valor}` +
        (fc.vencimento ? ` com vencimento em ${fmtData(fc.vencimento)}` : "") +
        (fc.descricao ? ` — ${fc.descricao}` : "") +
        ".",
      "",
      "Veja a cobrança completa (QR Code, impressão e código PIX copia-e-cola):",
      linkPublico,
      "",
      'Ou pague agora copiando o código PIX abaixo e colando na opção "PIX copia e cola" do seu banco:',
      "",
      fc.payload,
    ];
    const tel = soDigitos(fc.cliente_telefone || "");
    const destino = tel ? `https://wa.me/${tel.length <= 11 ? "55" + tel : tel}` : "https://wa.me/";
    window.open(`${destino}?text=${encodeURIComponent(partes.join("\n"))}`, "_blank", "noopener");
  }

  async function carregarCobrancas() {
    const data = await apiCall("listar_cobrancas");
    const cobrancas = data.cobrancas;
    if (chipCobrancas) chipCobrancas.textContent = cobrancas.length;
    cobrancasPorId.clear();
    cobrancas.forEach((fc) => cobrancasPorId.set(String(fc.id), fc));

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
            <a class="au-btn fb-btn-doc" href="/home/frutibank_cobranca?id=${fc.id}" target="_blank" rel="noopener">Ver / Imprimir</a>
            <button type="button" class="au-btn fb-btn-whats-mini" data-whatsapp>WhatsApp</button>
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

  tbodyCobrancas?.addEventListener("click", (e) => {
    if (!e.target.closest("[data-whatsapp]")) return;
    const tr = e.target.closest("tr[data-cobranca-id]");
    const fc = tr && cobrancasPorId.get(tr.dataset.cobrancaId);
    if (fc) abrirWhatsappCobranca(fc);
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
