/**
 * ADMIN_USUARIOS.JS
 * -----------------
 * Painel administrativo de usuários: listagem, criação de usuários locais,
 * alteração de perfil/ativo, reset de senha e "acessar como usuário".
 */

document.addEventListener("DOMContentLoaded", () => {
  const api = "../funcoes/admin";
  const tbody = document.querySelector("#tabela-usuarios tbody");
  const formCriar = document.getElementById("form-criar-usuario");
  const formBusca = document.getElementById("form-busca-usuario");

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

  function linhaUsuario(u) {
    const local = u.origem === "local";
    const ativo = Number(u.ativo) === 1;
    const perfis = ["usuario", "representante", "admin"];
    const labels = { usuario: "Usuário", representante: "Representante", admin: "Administrador" };
    const options = perfis
      .map((p) => `<option value="${p}" ${p === u.perfil ? "selected" : ""}>${labels[p]}</option>`)
      .join("");
    const criadoPor = u.criado_por_nome ? `<br><small>cadastrado por ${escapeHtml(u.criado_por_nome)}</small>` : "";

    return `<tr data-user-id="${u.id}">
      <td>${u.id}</td>
      <td>${escapeHtml(u.nome || "—")}${criadoPor}</td>
      <td>${escapeHtml(u.login || "—")}<br><small>${escapeHtml(u.email || "—")}</small></td>
      <td><span class="admin-usuarios-badge ${local ? "badge-local" : "badge-frutag"}">${local ? "Local" : "Frutag"}</span></td>
      <td><select class="admin-usuarios-perfil" data-perfil-select>${options}</select></td>
      <td>
        <label class="admin-offline-toggle">
          <input type="checkbox" data-toggle-ativo ${ativo ? "checked" : ""}>
          <span>${ativo ? "Ativo" : "Inativo"}</span>
        </label>
      </td>
      <td class="admin-usuarios-acoes">
        ${local ? `<button type="button" class="main-btn fundo-laranja" data-reset-senha>Nova senha</button>` : ""}
        <button type="button" class="main-btn fundo-azul" data-impersonar>Acessar caderno</button>
      </td>
    </tr>`;
  }

  async function carregarUsuarios(q = "") {
    const data = await apiGet("listar_usuarios.php", { q });
    tbody.innerHTML = data.usuarios.length
      ? data.usuarios.map(linhaUsuario).join("")
      : `<tr><td colspan="7">Nenhum usuário encontrado.</td></tr>`;
  }

  formCriar?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(formCriar);
    fd.append("acao", "criar");
    try {
      const data = await apiPost("salvar_usuario.php", fd);
      alert(data.msg);
      formCriar.reset();
      await carregarUsuarios();
    } catch (err) {
      alert(err.message);
    }
  });

  formBusca?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const q = new FormData(formBusca).get("q") || "";
    try {
      await carregarUsuarios(String(q));
    } catch (err) {
      alert(err.message);
    }
  });

  tbody?.addEventListener("change", async (e) => {
    const tr = e.target.closest("tr[data-user-id]");
    if (!tr) return;
    const userId = tr.dataset.userId;

    const selPerfil = e.target.closest("[data-perfil-select]");
    if (selPerfil) {
      try {
        await apiPost("salvar_usuario.php", { acao: "atualizar", user_id: userId, perfil: selPerfil.value });
      } catch (err) {
        alert(err.message);
        await carregarUsuarios();
      }
      return;
    }

    const chkAtivo = e.target.closest("[data-toggle-ativo]");
    if (chkAtivo) {
      const label = chkAtivo.closest("label")?.querySelector("span");
      try {
        await apiPost("salvar_usuario.php", { acao: "atualizar", user_id: userId, ativo: chkAtivo.checked ? "1" : "0" });
        if (label) label.textContent = chkAtivo.checked ? "Ativo" : "Inativo";
      } catch (err) {
        chkAtivo.checked = !chkAtivo.checked;
        alert(err.message);
      }
    }
  });

  tbody?.addEventListener("click", async (e) => {
    const tr = e.target.closest("tr[data-user-id]");
    if (!tr) return;
    const userId = tr.dataset.userId;

    if (e.target.closest("[data-reset-senha]")) {
      const senha = prompt("Nova senha para este usuário (mínimo 8 caracteres):");
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
      if (!confirm("Acessar o caderno deste usuário? Você poderá voltar ao seu perfil pelo aviso no topo da tela.")) return;
      try {
        const data = await apiPost("impersonar.php", { user_id: userId });
        window.location.href = data.redirect || "/home/";
      } catch (err) {
        alert(err.message);
      }
    }
  });

  carregarUsuarios().catch((err) => alert(err.message));
});
