/**
 * USUARIOS_CONTA.JS
 * -----------------
 * Gestão dos acessos (funcionários) da conta: listagem, criação,
 * alteração de papel, ativação/desativação e redefinição de senha.
 */

document.addEventListener("DOMContentLoaded", () => {
  const api = "../funcoes/conta";
  const tbody = document.querySelector("#tabela-funcionarios tbody");
  const formCriar = document.getElementById("form-criar-funcionario");
  const chipTotal = document.getElementById("cf-total");

  let meuFuncId = 0; // id do funcionário logado (0 = dono da conta)

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

  function linhaFuncionario(u) {
    const ativo = Number(u.ativo) === 1;
    const papeis = { apontador: "Apontador", admin: "Administrador" };
    const options = Object.entries(papeis)
      .map(([v, label]) => `<option value="${v}" ${v === u.papel_conta ? "selected" : ""}>${label}</option>`)
      .join("");
    const souEu = Number(u.id) === Number(meuFuncId);

    return `<tr data-user-id="${u.id}">
      <td><span class="au-nome">${escapeHtml(u.nome || "—")}</span>${souEu ? '<small class="au-sub">(você)</small>' : ""}</td>
      <td>${escapeHtml(u.login || "—")}<small class="au-sub">${escapeHtml(u.email || "—")}</small></td>
      <td><select class="au-select" data-papel-select aria-label="Papel na conta">${options}</select></td>
      <td>
        <label class="au-switch">
          <input type="checkbox" data-toggle-ativo ${ativo ? "checked" : ""}>
          <span class="au-slider"></span>
          <span class="au-state">${ativo ? "Ativo" : "Inativo"}</span>
        </label>
      </td>
      <td class="au-acoes">
        <button type="button" class="au-btn au-btn-senha" data-reset-senha>Nova senha</button>
      </td>
    </tr>`;
  }

  async function carregarFuncionarios() {
    const data = await apiGet("listar_usuarios.php");
    meuFuncId = Number(data.func_id || 0);
    if (chipTotal) {
      const ativos = data.usuarios.filter((u) => Number(u.ativo) === 1).length;
      chipTotal.textContent = data.liberado
        ? `${ativos} de ${data.limite} acesso${data.limite === 1 ? "" : "s"} em uso`
        : `${data.usuarios.length} acesso${data.usuarios.length === 1 ? "" : "s"}`;
    }
    tbody.innerHTML = data.usuarios.length
      ? data.usuarios.map(linhaFuncionario).join("")
      : `<tr class="au-vazio"><td colspan="5">Nenhum acesso criado ainda.</td></tr>`;
  }

  function mensagemCredencialIndisponivel(campo, origem) {
    if (origem === "frutag") return `${campo} já está em uso na Frutag. Escolha outro.`;
    return `${campo} já está em uso. Escolha outro.`;
  }

  async function checarDisponibilidadeCampo(valor, ehEmail = false) {
    const v = String(valor || "").trim();
    if (!v) return { disponivel: true, origem: null };
    if (ehEmail && !v.includes("@")) return { disponivel: true, origem: null };
    return apiGet("verificar_disponibilidade.php", { valor: v });
  }

  function aplicarHintDisponibilidade(input, hint, data) {
    const ok = !!data.disponivel;
    input.dataset.disponivel = ok ? "1" : "0";
    hint.textContent = ok
      ? "✓ Disponível"
      : data.origem === "frutag"
        ? "✗ Já está em uso na Frutag"
        : "✗ Já está em uso";
    hint.style.color = ok ? "#2e7d32" : "#c62828";
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
        const data = await checarDisponibilidadeCampo(valor, ehEmail);
        aplicarHintDisponibilidade(input, hint, data);
      } catch (err) {
        hint.textContent = "Não foi possível verificar agora.";
        hint.style.color = "#c62828";
        input.dataset.disponivel = "";
      }
    }, 400);

    input.addEventListener("input", verificar);
  }

  function limparDisponibilidade() {
    formCriar?.querySelectorAll("[data-hint-disponibilidade]").forEach((el) => (el.textContent = ""));
    formCriar?.querySelectorAll("input").forEach((el) => (el.dataset.disponivel = ""));
  }

  monitorarDisponibilidade("cf-login");
  monitorarDisponibilidade("cf-email", true);

  formCriar?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const login = document.getElementById("cf-login")?.value?.trim() || "";
    const email = document.getElementById("cf-email")?.value?.trim() || "";

    try {
      const rLogin = await checarDisponibilidadeCampo(login);
      if (!rLogin.disponivel) {
        alert(mensagemCredencialIndisponivel("Login", rLogin.origem));
        return;
      }
      if (email) {
        const rEmail = await checarDisponibilidadeCampo(email, true);
        if (!rEmail.disponivel) {
          alert(mensagemCredencialIndisponivel("E-mail", rEmail.origem));
          return;
        }
      }
    } catch (err) {
      alert(err.message || "Não foi possível verificar login/e-mail. Tente novamente.");
      return;
    }

    const fd = new FormData(formCriar);
    fd.append("acao", "criar");
    try {
      const data = await apiPost("salvar_usuario.php", fd);
      alert(data.msg);
      formCriar.reset();
      limparDisponibilidade();
      await carregarFuncionarios();
    } catch (err) {
      alert(err.message);
    }
  });

  tbody?.addEventListener("change", async (e) => {
    const tr = e.target.closest("tr[data-user-id]");
    if (!tr) return;
    const userId = tr.dataset.userId;

    const selPapel = e.target.closest("[data-papel-select]");
    if (selPapel) {
      try {
        await apiPost("salvar_usuario.php", { acao: "atualizar", user_id: userId, papel_conta: selPapel.value });
      } catch (err) {
        alert(err.message);
        await carregarFuncionarios();
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
    }
  });

  tbody?.addEventListener("click", async (e) => {
    const tr = e.target.closest("tr[data-user-id]");
    if (!tr) return;
    const userId = tr.dataset.userId;

    if (e.target.closest("[data-reset-senha]")) {
      const senha = prompt("Nova senha para este acesso (mínimo 8 caracteres):");
      if (!senha) return;
      try {
        const data = await apiPost("resetar_senha.php", { user_id: userId, senha });
        alert(data.msg);
      } catch (err) {
        alert(err.message);
      }
    }
  });

  carregarFuncionarios().catch((err) => alert(err.message));
});
