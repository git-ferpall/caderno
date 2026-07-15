/**
 * MEUS_CLIENTES.JS
 * ----------------
 * Painel do representante: cadastro de clientes (usuários locais)
 * e acesso ao caderno de cada cliente. Só lista clientes criados
 * pelo próprio representante (filtro também aplicado no backend).
 */

document.addEventListener("DOMContentLoaded", () => {
  const api = "../funcoes/admin";
  const tbody = document.querySelector("#tabela-clientes tbody");
  const formCriar = document.getElementById("form-criar-cliente");
  const formBusca = document.getElementById("form-busca-cliente");

  function escapeHtml(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  async function apiGet(endpoint, params = {}) {
    const url = new URL(`${api}/${endpoint}`, window.location.href);
    Object.entries(params).forEach(([k, v]) => v !== "" && url.searchParams.set(k, v));
    const r = await fetch(url, { credentials: "same-origin" });
    const data = await r.json();
    if (!data.ok) throw new Error(data.msg || "Erro na requisição");
    return data;
  }

  async function apiPost(endpoint, body) {
    const fd = body instanceof FormData ? body : new FormData();
    if (!(body instanceof FormData)) {
      Object.entries(body || {}).forEach(([k, v]) => fd.append(k, v));
    }
    const r = await fetch(`${api}/${endpoint}`, {
      method: "POST",
      credentials: "same-origin",
      body: fd,
    });
    const data = await r.json();
    if (!data.ok) throw new Error(data.msg || "Erro na requisição");
    return data;
  }

  function linhaCliente(u) {
    const ativo = Number(u.ativo) === 1;
    return `<tr data-user-id="${u.id}">
      <td>${escapeHtml(u.nome || "—")}</td>
      <td>${escapeHtml(u.login || "—")}<br><small>${escapeHtml(u.email || "—")}</small></td>
      <td>
        <label class="admin-offline-toggle">
          <input type="checkbox" data-toggle-ativo ${ativo ? "checked" : ""}>
          <span>${ativo ? "Ativo" : "Inativo"}</span>
        </label>
      </td>
      <td class="admin-usuarios-acoes">
        <button type="button" class="main-btn fundo-laranja" data-reset-senha>Nova senha</button>
        <button type="button" class="main-btn fundo-azul" data-impersonar>Acessar caderno</button>
      </td>
    </tr>`;
  }

  async function carregarClientes(q = "") {
    const data = await apiGet("listar_usuarios.php", { q, meus: "1" });
    tbody.innerHTML = data.usuarios.length
      ? data.usuarios.map(linhaCliente).join("")
      : `<tr><td colspan="4">Nenhum cliente cadastrado ainda.</td></tr>`;
  }

  formCriar?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(formCriar);
    fd.append("acao", "criar");
    try {
      const data = await apiPost("salvar_usuario.php", fd);
      alert(data.msg);
      formCriar.reset();
      await carregarClientes();
    } catch (err) {
      alert(err.message);
    }
  });

  formBusca?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const q = new FormData(formBusca).get("q") || "";
    try {
      await carregarClientes(String(q));
    } catch (err) {
      alert(err.message);
    }
  });

  tbody?.addEventListener("change", async (e) => {
    const tr = e.target.closest("tr[data-user-id]");
    if (!tr) return;
    const chkAtivo = e.target.closest("[data-toggle-ativo]");
    if (!chkAtivo) return;
    const label = chkAtivo.closest("label")?.querySelector("span");
    try {
      await apiPost("salvar_usuario.php", { acao: "atualizar", user_id: tr.dataset.userId, ativo: chkAtivo.checked ? "1" : "0" });
      if (label) label.textContent = chkAtivo.checked ? "Ativo" : "Inativo";
    } catch (err) {
      chkAtivo.checked = !chkAtivo.checked;
      alert(err.message);
    }
  });

  tbody?.addEventListener("click", async (e) => {
    const tr = e.target.closest("tr[data-user-id]");
    if (!tr) return;
    const userId = tr.dataset.userId;

    if (e.target.closest("[data-reset-senha]")) {
      const senha = prompt("Nova senha para este cliente (mínimo 8 caracteres):");
      if (!senha) return;
      try {
        const data = await apiPost("resetar_senha.php", { user_id: userId, senha });
        alert(data.msg);
      } catch (err) {
        alert(err.message);
      }
      return;
    }

    if (e.target.closest("[data-impersonar]")) {
      if (!confirm("Acessar o caderno deste cliente? Você poderá voltar ao seu perfil pelo aviso no topo da tela.")) return;
      try {
        const data = await apiPost("impersonar.php", { user_id: userId });
        window.location.href = data.redirect || "/home/";
      } catch (err) {
        alert(err.message);
      }
    }
  });

  carregarClientes().catch((err) => alert(err.message));
});
