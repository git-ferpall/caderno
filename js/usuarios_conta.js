/**
 * USUARIOS_CONTA.JS — Gestão de acessos da conta
 */

document.addEventListener("DOMContentLoaded", () => {
  const api = "../funcoes/conta";
  const tbody = document.querySelector("#tabela-funcionarios tbody");
  const formCriar = document.getElementById("form-criar-funcionario");
  const chipTotal = document.getElementById("cf-total");
  const boxPropsCriar = document.getElementById("cf-propriedades");
  const modal = document.getElementById("modal-propriedades");
  const modalLista = document.getElementById("modal-prop-lista");
  const modalSub = document.getElementById("modal-prop-subtitulo");
  const btnModalSalvar = document.getElementById("modal-prop-salvar");
  const btnModalCancelar = document.getElementById("modal-prop-cancelar");
  const btnModalFechar = document.getElementById("modal-prop-fechar");

  let meuFuncId = 0;
  let propriedadesConta = [];
  let modalUserId = 0;

  /** Popup padrão do Caderno (popup-success / popup-failed em include/popups.php). */
  function showPopupSucesso(mensagem) {
    const overlay = document.getElementById("popup-overlay");
    const popupSuccess = document.getElementById("popup-success");
    if (!overlay || !popupSuccess) {
      alert(mensagem);
      return;
    }
    document.querySelectorAll(".popup-box").forEach((p) => p.classList.add("d-none"));
    overlay.classList.remove("d-none");
    popupSuccess.classList.remove("d-none");
    const title = popupSuccess.querySelector(".popup-title");
    if (title) title.textContent = mensagem;
  }

  function showPopupErro(mensagem, titulo = "Não foi possível concluir") {
    const overlay = document.getElementById("popup-overlay");
    const popupFailed = document.getElementById("popup-failed");
    if (!overlay || !popupFailed) {
      alert(mensagem);
      return;
    }
    document.querySelectorAll(".popup-box").forEach((p) => p.classList.add("d-none"));
    overlay.classList.remove("d-none");
    popupFailed.classList.remove("d-none");
    const title = popupFailed.querySelector(".popup-title");
    const text = popupFailed.querySelector(".popup-text");
    if (title) title.textContent = titulo;
    if (text) text.textContent = mensagem;
  }

  const ICON_PIN = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>`;
  const ICON_KEY = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.78 7.78 5.5 5.5 0 0 1 7.78-7.78zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>`;

  function escapeHtml(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function iniciais(nome) {
    const parts = String(nome || "?").trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return "?";
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
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

  function labelPropriedade(p) {
    const local = [p.endereco_cidade, p.endereco_uf].filter(Boolean).join(" / ");
    return local ? `${p.nome_razao} — ${local}` : (p.nome_razao || `Propriedade #${p.id}`);
  }

  function renderCheckboxesPropriedades(container, selecionadas = null) {
    if (!container) return;
    if (!propriedadesConta.length) {
      container.innerHTML = '<p class="uc-props-vazio">Nenhuma propriedade cadastrada nesta conta.</p>';
      return;
    }
    const idsSel = selecionadas === null
      ? propriedadesConta.map((p) => Number(p.id))
      : selecionadas.map(Number);

    container.innerHTML = propriedadesConta.map((p) => {
      const checked = idsSel.includes(Number(p.id)) ? "checked" : "";
      return `<label class="uc-prop-item">
        <input type="checkbox" class="cf-prop-check" value="${p.id}" ${checked}>
        <span>${escapeHtml(labelPropriedade(p))}</span>
      </label>`;
    }).join("");
  }

  function marcarTodasProps(container, marcar) {
    container?.querySelectorAll(".cf-prop-check").forEach((el) => {
      el.checked = marcar;
    });
  }

  function idsPropriedadesMarcadas(container) {
    if (!container) return [];
    return [...container.querySelectorAll(".cf-prop-check:checked")].map((el) => el.value);
  }

  function linhaFuncionario(u) {
    const ativo = Number(u.ativo) === 1;
    const souEu = Number(u.id) === Number(meuFuncId);
    const isAdmin = u.papel_conta === "admin";
    const qtdProps = u.propriedades === null ? propriedadesConta.length : Number(u.propriedades_qtd || 0);
    const totalProps = propriedadesConta.length;
    const propLabel = totalProps ? `${qtdProps}/${totalProps}` : "—";
    const todasProps = u.propriedades === null && totalProps > 0;

    const papeis = { apontador: "Apontador", admin: "Administrador" };
    const options = Object.entries(papeis)
      .map(([v, label]) => `<option value="${v}" ${v === u.papel_conta ? "selected" : ""}>${label}</option>`)
      .join("");

    return `<tr data-user-id="${u.id}" data-propriedades="${encodeURIComponent(JSON.stringify(u.propriedades))}" class="${ativo ? "" : "uc-row--off"}">
      <td>
        <div class="uc-user-cell">
          <div class="uc-avatar${isAdmin ? " uc-avatar--admin" : ""}" aria-hidden="true">${escapeHtml(iniciais(u.nome))}</div>
          <div>
            <span class="uc-user-name">${escapeHtml(u.nome || "—")}${souEu ? '<span class="uc-badge-me">Você</span>' : ""}</span>
            <span class="uc-user-meta">${isAdmin ? "Administrador da conta" : "Apontador de campo"}</span>
          </div>
        </div>
      </td>
      <td>
        <span class="uc-user-name" style="font-size:13px">${escapeHtml(u.login || "—")}</span>
        <span class="uc-user-meta">${escapeHtml(u.email || "Sem e-mail")}</span>
      </td>
      <td>
        <select class="uc-papel-select" data-papel-select aria-label="Papel na conta">${options}</select>
      </td>
      <td>
        <button type="button" class="uc-btn-props" data-editar-props title="Editar propriedades">
          ${ICON_PIN}
          <span>${propLabel}${todasProps ? " · todas" : ""}</span>
        </button>
      </td>
      <td>
        <label class="au-switch">
          <input type="checkbox" data-toggle-ativo ${ativo ? "checked" : ""}>
          <span class="au-slider"></span>
          <span class="au-state">${ativo ? "Ativo" : "Inativo"}</span>
        </label>
      </td>
      <td class="au-acoes">
        <button type="button" class="uc-btn-icon" data-reset-senha title="Redefinir senha" aria-label="Redefinir senha">${ICON_KEY}</button>
      </td>
    </tr>`;
  }

  async function carregarPropriedadesConta() {
    const data = await apiGet("listar_propriedades.php");
    propriedadesConta = data.propriedades || [];
    renderCheckboxesPropriedades(boxPropsCriar, null);
  }

  async function carregarFuncionarios() {
    const data = await apiGet("listar_usuarios.php");
    meuFuncId = Number(data.func_id || 0);
    if (chipTotal) {
      const total = data.usuarios.length;
      const ativos = data.usuarios.filter((u) => Number(u.ativo) === 1).length;
      chipTotal.textContent = data.liberado
        ? `${ativos} ativo${ativos === 1 ? "" : "s"} · ${total} cadastrado${total === 1 ? "" : "s"}`
        : `${total} cadastrado${total === 1 ? "" : "s"}`;
    }
    tbody.innerHTML = data.usuarios.length
      ? data.usuarios.map(linhaFuncionario).join("")
      : `<tr><td colspan="6" class="uc-empty">
          <div class="uc-empty-icon" aria-hidden="true">👥</div>
          <p>Nenhum acesso criado ainda.<br>Cadastre o primeiro membro da equipe acima.</p>
        </td></tr>`;
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
        ? "✗ Em uso na Frutag"
        : "✗ Já em uso";
    hint.style.color = ok ? "#2e7d32" : "#c62828";
    hint.style.fontSize = "12px";
    hint.style.marginTop = "4px";
    hint.style.display = "block";
  }

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
    input.insertAdjacentElement("afterend", hint);

    const verificar = debounce(async () => {
      const valor = input.value.trim();
      hint.textContent = "";
      input.dataset.disponivel = "";
      if (!valor || (ehEmail && !valor.includes("@"))) return;
      try {
        const data = await checarDisponibilidadeCampo(valor, ehEmail);
        aplicarHintDisponibilidade(input, hint, data);
      } catch {
        hint.textContent = "Não foi possível verificar.";
        hint.style.color = "#c62828";
        input.dataset.disponivel = "";
      }
    }, 400);

    input.addEventListener("input", verificar);
  }

  function limparDisponibilidade() {
    formCriar?.querySelectorAll("[data-hint-disponibilidade]").forEach((el) => (el.textContent = ""));
    formCriar?.querySelectorAll("input").forEach((el) => {
      if (!el.classList.contains("cf-prop-check")) el.dataset.disponivel = "";
    });
    renderCheckboxesPropriedades(boxPropsCriar, null);
  }

  function abrirModalPropriedades(tr) {
    modalUserId = Number(tr.dataset.userId);
    let selecionadas = null;
    try {
      const raw = tr.getAttribute("data-propriedades");
      selecionadas = raw ? JSON.parse(decodeURIComponent(raw)) : null;
    } catch {
      selecionadas = null;
    }
    const nome = tr.querySelector(".uc-user-name")?.textContent?.replace(/Você/g, "").trim() || "Funcionário";
    if (modalSub) modalSub.textContent = `Defina as propriedades que ${nome} poderá acessar.`;
    renderCheckboxesPropriedades(modalLista, selecionadas);
    modal?.classList.remove("d-none");
    document.body.style.overflow = "hidden";
  }

  function fecharModalPropriedades() {
    modalUserId = 0;
    modal?.classList.add("d-none");
    document.body.style.overflow = "";
  }

  document.querySelector("[data-props-all]")?.addEventListener("click", () => marcarTodasProps(boxPropsCriar, true));
  document.querySelector("[data-props-none]")?.addEventListener("click", () => marcarTodasProps(boxPropsCriar, false));
  document.querySelector("[data-modal-props-all]")?.addEventListener("click", () => marcarTodasProps(modalLista, true));
  document.querySelector("[data-modal-props-none]")?.addEventListener("click", () => marcarTodasProps(modalLista, false));

  monitorarDisponibilidade("cf-login");
  monitorarDisponibilidade("cf-email", true);

  btnModalCancelar?.addEventListener("click", fecharModalPropriedades);
  btnModalFechar?.addEventListener("click", fecharModalPropriedades);
  modal?.addEventListener("click", (e) => {
    if (e.target === modal) fecharModalPropriedades();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal && !modal.classList.contains("d-none")) fecharModalPropriedades();
  });

  btnModalSalvar?.addEventListener("click", async () => {
    if (!modalUserId) return;
    const props = idsPropriedadesMarcadas(modalLista);
    if (!props.length) {
      showPopupErro("Selecione ao menos uma propriedade.");
      return;
    }
    try {
      const fd = new FormData();
      fd.append("acao", "propriedades");
      fd.append("user_id", String(modalUserId));
      props.forEach((id) => fd.append("propriedades[]", id));
      const data = await apiPost("salvar_usuario.php", fd);
      showPopupSucesso(data.msg);
      fecharModalPropriedades();
      await carregarFuncionarios();
    } catch (err) {
      showPopupErro(err.message);
    }
  });

  formCriar?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const login = document.getElementById("cf-login")?.value?.trim() || "";
    const email = document.getElementById("cf-email")?.value?.trim() || "";
    const props = idsPropriedadesMarcadas(boxPropsCriar);

    if (!props.length) {
      showPopupErro("Selecione ao menos uma propriedade para este acesso.");
      return;
    }

    try {
      const rLogin = await checarDisponibilidadeCampo(login);
      if (!rLogin.disponivel) {
        showPopupErro(mensagemCredencialIndisponivel("Login", rLogin.origem));
        return;
      }
      if (email) {
        const rEmail = await checarDisponibilidadeCampo(email, true);
        if (!rEmail.disponivel) {
          showPopupErro(mensagemCredencialIndisponivel("E-mail", rEmail.origem));
          return;
        }
      }
    } catch (err) {
      showPopupErro(err.message || "Não foi possível verificar login/e-mail.");
      return;
    }

    const fd = new FormData(formCriar);
    fd.append("acao", "criar");
    props.forEach((id) => fd.append("propriedades[]", id));
    try {
      const data = await apiPost("salvar_usuario.php", fd);
      showPopupSucesso(data.msg);
      formCriar.reset();
      limparDisponibilidade();
      await carregarFuncionarios();
    } catch (err) {
      showPopupErro(err.message);
    }
  });

  tbody?.addEventListener("change", async (e) => {
    const tr = e.target.closest("tr[data-user-id]");
    if (!tr) return;
    const userId = tr.dataset.userId;

    if (e.target.closest("[data-papel-select]")) {
      try {
        await apiPost("salvar_usuario.php", {
          acao: "atualizar",
          user_id: userId,
          papel_conta: e.target.value,
        });
        await carregarFuncionarios();
      } catch (err) {
        showPopupErro(err.message);
        await carregarFuncionarios();
      }
      return;
    }

    const chkAtivo = e.target.closest("[data-toggle-ativo]");
    if (chkAtivo) {
      const label = chkAtivo.closest("label")?.querySelector(".au-state");
      try {
        await apiPost("salvar_usuario.php", {
          acao: "atualizar",
          user_id: userId,
          ativo: chkAtivo.checked ? "1" : "0",
        });
        if (label) label.textContent = chkAtivo.checked ? "Ativo" : "Inativo";
        tr.classList.toggle("uc-row--off", !chkAtivo.checked);
      } catch (err) {
        chkAtivo.checked = !chkAtivo.checked;
        showPopupErro(err.message);
      }
    }
  });

  tbody?.addEventListener("click", async (e) => {
    const tr = e.target.closest("tr[data-user-id]");
    if (!tr) return;

    if (e.target.closest("[data-editar-props]")) {
      abrirModalPropriedades(tr);
      return;
    }

    if (e.target.closest("[data-reset-senha]")) {
      const senha = prompt("Nova senha para este acesso (mínimo 8 caracteres):");
      if (!senha) return;
      try {
        const data = await apiPost("resetar_senha.php", { user_id: tr.dataset.userId, senha });
        showPopupSucesso(data.msg);
      } catch (err) {
        showPopupErro(err.message);
      }
    }
  });

  Promise.all([carregarPropriedadesConta(), carregarFuncionarios()]).catch((err) => showPopupErro(err.message));
});
