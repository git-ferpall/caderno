document.addEventListener("DOMContentLoaded", () => {
  const api = "../funcoes/offline/admin_api.php";
  const tbodyAdmins = document.querySelector("#tabela-admins tbody");
  const tbodyUsuarios = document.querySelector("#tabela-usuarios tbody");

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

  function fmtData(iso) {
    if (!iso) return "—";
    const d = new Date(iso);
    return Number.isNaN(d.getTime()) ? iso : d.toLocaleDateString("pt-BR");
  }

  async function carregarAdmins() {
    const data = await apiCall("listar_admins");
    tbodyAdmins.innerHTML = data.admins.length
      ? data.admins
          .map(
            (a) => `<tr>
          <td>${a.user_id}</td>
          <td>${escapeHtml(a.nome || "—")}</td>
          <td>${escapeHtml(a.email || "—")}</td>
          <td>${fmtData(a.criado_em)}</td>
          <td><button type="button" class="admin-offline-btn-remove" data-remove-admin="${a.user_id}">Remover</button></td>
        </tr>`
          )
          .join("")
      : `<tr><td colspan="5">Nenhum administrador cadastrado.</td></tr>`;
  }

  async function carregarUsuarios(q = "") {
    const data = await apiCall("listar_usuarios", { q });
    tbodyUsuarios.innerHTML = data.usuarios.length
      ? data.usuarios
          .map((u) => {
            const checked = Number(u.offline_habilitado) !== 0 ? "checked" : "";
            const label = u.propriedade_ativa || u.nome_razao || "—";
            const statusLabel = checked ? "Ativo (padrão)" : "Desativado";
            return `<tr>
          <td>${u.user_id}</td>
          <td>${escapeHtml(label)}</td>
          <td>${escapeHtml(u.email || "—")}</td>
          <td>
            <label class="admin-offline-toggle">
              <input type="checkbox" data-toggle-offline="${u.user_id}" ${checked}>
              <span>${statusLabel}</span>
            </label>
          </td>
        </tr>`;
          })
          .join("")
      : `<tr><td colspan="4">Nenhum cliente encontrado.</td></tr>`;
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  document.getElementById("form-add-admin")?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      await apiCall("adicionar_admin", { method: "POST", body: fd });
      e.target.reset();
      await carregarAdmins();
    } catch (err) {
      alert(err.message);
    }
  });

  document.getElementById("form-busca-usuario")?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const q = new FormData(e.target).get("q") || "";
    try {
      await carregarUsuarios(String(q));
    } catch (err) {
      alert(err.message);
    }
  });

  tbodyAdmins?.addEventListener("click", async (e) => {
    const btn = e.target.closest("[data-remove-admin]");
    if (!btn) return;
    if (!confirm("Remover este administrador?")) return;
    try {
      await apiCall("remover_admin", {
        method: "POST",
        body: { user_id: btn.dataset.removeAdmin },
      });
      await carregarAdmins();
    } catch (err) {
      alert(err.message);
    }
  });

  tbodyUsuarios?.addEventListener("change", async (e) => {
    const input = e.target.closest("[data-toggle-offline]");
    if (!input) return;
    const userId = input.dataset.toggleOffline;
    const habilitar = input.checked ? "1" : "0";
    const label = input.closest("label")?.querySelector("span");
    try {
      await apiCall("toggle_usuario", {
        method: "POST",
        body: { user_id: userId, habilitar },
      });
      if (label) label.textContent = input.checked ? "Ativo (padrão)" : "Desativado";
    } catch (err) {
      input.checked = !input.checked;
      alert(err.message);
    }
  });

  carregarAdmins().catch((err) => alert(err.message));
  carregarUsuarios().catch((err) => alert(err.message));
});
