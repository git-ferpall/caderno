/**
 * FRUTIBANK.JS
 * ------------
 * Painel do Frutibank: chave PIX do recebedor, clientes de cobrança
 * (CPF/CNPJ) e geração de cobranças PIX imprimíveis.
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

  function escapeHtml(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function fmtDoc(doc) {
    const d = String(doc || "").replace(/\D/g, "");
    if (d.length === 11) return d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
    if (d.length === 14) return d.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
    return doc || "—";
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
    } else if (opts.q) {
      url += `&q=${encodeURIComponent(opts.q)}`;
    }

    const r = await fetch(url, init);
    const data = await r.json();
    if (!data.ok) throw new Error(data.msg || "Erro na requisição");
    return data;
  }

  /* ---------------- Chave PIX ---------------- */

  async function carregarConfig() {
    const data = await apiCall("get_config");
    const c = data.config;
    if (chipConfig) chipConfig.textContent = c ? "OK" : "Pendente";
    if (!c) return;
    formConfig.tipo_chave.value = c.tipo_chave;
    formConfig.chave_pix.value = c.chave_pix;
    formConfig.nome_recebedor.value = c.nome_recebedor;
    formConfig.cidade.value = c.cidade;
    // chave já configurada: abre direto em Cobranças (se o usuário não escolheu outra aba)
    if (!tabEscolhidaPeloUsuario) abrirTab("cobrancas");
  }

  formConfig?.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      const data = await apiCall("salvar_config", { method: "POST", body: new FormData(formConfig) });
      alert(data.msg);
      await carregarConfig();
    } catch (err) {
      alert(err.message);
    }
  });

  /* ---------------- Clientes ---------------- */

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
    try {
      const data = await apiCall("salvar_cliente", { method: "POST", body: new FormData(formCliente) });
      alert(data.msg);
      formCliente.reset();
      await carregarClientes();
    } catch (err) {
      alert(err.message);
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
      if (!confirm("Excluir este cliente?")) return;
      try {
        await apiCall("excluir_cliente", { method: "POST", body: { cliente_id: tr.dataset.clienteId } });
        await carregarClientes();
      } catch (err) {
        alert(err.message);
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
      alert("Selecione um cliente para cobrar.");
      return;
    }
    try {
      const data = await apiCall("criar_cobranca", { method: "POST", body: new FormData(formCobranca) });
      formCobranca.reset();
      await Promise.all([carregarCobrancas(), carregarClientes()]);
      if (data.url) window.open(data.url, "_blank", "noopener");
    } catch (err) {
      alert(err.message);
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
      alert(err.message);
      await carregarCobrancas();
    }
  });

  /* ---------------- Inicialização ---------------- */

  Promise.all([carregarConfig(), carregarClientes(), carregarCobrancas()]).catch((err) => alert(err.message));
});
