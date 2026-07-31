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
  const chipTotal = document.getElementById("au-total");

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
    const criadoPor = u.criado_por_nome
      ? `<small class="au-sub">cadastrado por ${escapeHtml(u.criado_por_nome)}</small>`
      : "";

    return `<tr data-user-id="${u.id}">
      <td class="au-id">${u.id}</td>
      <td><span class="au-nome">${escapeHtml(u.nome || "—")}</span>${criadoPor}</td>
      <td>${escapeHtml(u.login || "—")}<small class="au-sub">${escapeHtml(u.email || "—")}</small></td>
      <td><span class="au-badge ${local ? "au-badge-local" : "au-badge-frutag"}">${local ? "Local" : "Frutag"}</span></td>
      <td><select class="au-select" data-perfil-select aria-label="Perfil do usuário">${options}</select></td>
      <td>
        <label class="au-switch">
          <input type="checkbox" data-toggle-ativo ${ativo ? "checked" : ""}>
          <span class="au-slider"></span>
          <span class="au-state">${ativo ? "Ativo" : "Inativo"}</span>
        </label>
      </td>
      <td>
        <label class="au-switch">
          <input type="checkbox" data-toggle-frutibank ${Number(u.frutibank) === 1 ? "checked" : ""}>
          <span class="au-slider"></span>
          <span class="au-state">${Number(u.frutibank) === 1 ? "Liberado" : "—"}</span>
        </label>
      </td>
      <td>
        <label class="au-switch">
          <input type="checkbox" data-toggle-func ${Number(u.func_liberado) === 1 ? "checked" : ""}>
          <span class="au-slider"></span>
          <span class="au-state">${Number(u.func_liberado) === 1 ? "Liberado" : "—"}</span>
        </label>
        <input type="number" data-func-limite min="1" max="99" step="1"
          value="${Number(u.func_limite || 1)}" ${Number(u.func_liberado) === 1 ? "" : "disabled"}
          title="Quantidade máxima de acessos ativos"
          aria-label="Quantidade máxima de funcionários"
          style="width:56px;margin-left:6px">
      </td>
      <td class="au-acoes">
        ${local ? `<button type="button" class="au-btn au-btn-senha" data-reset-senha>Nova senha</button>` : ""}
        <button type="button" class="au-btn au-btn-acessar" data-impersonar>Acessar caderno</button>
      </td>
    </tr>`;
  }

  async function carregarUsuarios(q = "") {
    const data = await apiGet("listar_usuarios.php", { q });
    if (chipTotal) {
      chipTotal.textContent = `${data.usuarios.length} usuário${data.usuarios.length === 1 ? "" : "s"}`;
    }
    tbody.innerHTML = data.usuarios.length
      ? data.usuarios.map(linhaUsuario).join("")
      : `<tr class="au-vazio"><td colspan="9">Nenhum usuário encontrado.</td></tr>`;
  }

  // --- Verificação de disponibilidade de login/e-mail (em tempo real) ---
  function debounce(fn, ms) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  }

  function monitorarDisponibilidade(inputId, ehEmail = false) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const hint = document.createElement("small");
    hint.setAttribute("data-hint-disponibilidade", "");
    hint.style.display = "block";
    hint.style.marginTop = "4px";
    input.insertAdjacentElement("afterend", hint);

    const verificar = debounce(async () => {
      const valor = input.value.trim();
      hint.textContent = "";
      input.dataset.disponivel = "";
      if (!valor || (ehEmail && !valor.includes("@"))) return;
      try {
        const data = await apiGet("verificar_disponibilidade.php", { valor });
        const ok = !!data.disponivel;
        input.dataset.disponivel = ok ? "1" : "0";
        hint.textContent = ok ? "✓ Disponível" : "✗ Já está em uso";
        hint.style.color = ok ? "#2e7d32" : "#c62828";
      } catch (err) {
        // silencioso: a validação definitiva acontece no envio
      }
    }, 400);

    input.addEventListener("input", verificar);
  }

  function limparDisponibilidade() {
    formCriar?.querySelectorAll("[data-hint-disponibilidade]").forEach((el) => (el.textContent = ""));
    formCriar?.querySelectorAll("input").forEach((el) => (el.dataset.disponivel = ""));
  }

  monitorarDisponibilidade("cu-login");
  monitorarDisponibilidade("cu-email", true);

  formCriar?.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (["cu-login", "cu-email"].some((id) => document.getElementById(id)?.dataset.disponivel === "0")) {
      alert("Login ou e-mail já está em uso. Escolha outro.");
      return;
    }
    const fd = new FormData(formCriar);
    fd.append("acao", "criar");
    try {
      const data = await apiPost("salvar_usuario.php", fd);
      alert(data.msg);
      formCriar.reset();
      limparDisponibilidade();
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
      const label = chkAtivo.closest("label")?.querySelector(".au-state");
      try {
        await apiPost("salvar_usuario.php", { acao: "atualizar", user_id: userId, ativo: chkAtivo.checked ? "1" : "0" });
        if (label) label.textContent = chkAtivo.checked ? "Ativo" : "Inativo";
      } catch (err) {
        chkAtivo.checked = !chkAtivo.checked;
        alert(err.message);
      }
      return;
    }

    const chkFrutibank = e.target.closest("[data-toggle-frutibank]");
    if (chkFrutibank) {
      const label = chkFrutibank.closest("label")?.querySelector(".au-state");
      try {
        await apiPost("salvar_usuario.php", { acao: "atualizar", user_id: userId, frutibank: chkFrutibank.checked ? "1" : "0" });
        if (label) label.textContent = chkFrutibank.checked ? "Liberado" : "—";
      } catch (err) {
        chkFrutibank.checked = !chkFrutibank.checked;
        alert(err.message);
      }
      return;
    }

    // Liberação de funcionários da conta (toggle) + quantidade máxima
    const inputLimite = tr.querySelector("[data-func-limite]");
    const chkFunc = e.target.closest("[data-toggle-func]");
    if (chkFunc) {
      const label = chkFunc.closest("label")?.querySelector(".au-state");
      const limite = Math.max(1, Number(inputLimite?.value || 1));
      try {
        await apiPost("salvar_usuario.php", {
          acao: "atualizar",
          user_id: userId,
          funcionarios: chkFunc.checked ? "1" : "0",
          func_limite: String(limite),
        });
        if (label) label.textContent = chkFunc.checked ? "Liberado" : "—";
        if (inputLimite) inputLimite.disabled = !chkFunc.checked;
      } catch (err) {
        chkFunc.checked = !chkFunc.checked;
        alert(err.message);
      }
      return;
    }

    const limiteAlterado = e.target.closest("[data-func-limite]");
    if (limiteAlterado && !limiteAlterado.disabled) {
      const limite = Math.max(1, Math.min(99, Number(limiteAlterado.value || 1)));
      limiteAlterado.value = limite;
      try {
        await apiPost("salvar_usuario.php", {
          acao: "atualizar",
          user_id: userId,
          funcionarios: "1",
          func_limite: String(limite),
        });
      } catch (err) {
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
