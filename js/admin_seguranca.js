/**
 * Painel de auditoria de segurança
 */

document.addEventListener("DOMContentLoaded", () => {
  const api = "../funcoes/admin/listar_login_attempts.php";
  const formFiltros = document.getElementById("form-filtros");
  const tbody = document.querySelector("#tabela-tentativas tbody");
  const pagination = document.getElementById("as-pagination");
  const motivosEl = document.getElementById("sec-motivos");
  const ipsEl = document.getElementById("sec-ips");
  const refreshBtn = document.getElementById("sec-refresh");
  const panel = document.getElementById("sec-panel");

  let currentPage = 1;

  const motivoLabels = {
    senha_invalida: "Senha inválida",
    captcha_fail: "reCAPTCHA falhou",
    captcha_vazio: "reCAPTCHA vazio",
    bloqueado: "Rate limit",
    sem_permissao: "Sem permissão",
    auth_fail: "Falha na API",
    ok_local: "Sucesso (local)",
    ok_frutag: "Sucesso (Frutag)",
  };

  function esc(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function shortHash(h) {
    const v = String(h || "");
    return v.length <= 12 ? v : `${v.slice(0, 6)}…${v.slice(-4)}`;
  }

  function fmtDate(v) {
    if (!v) return "—";
    const d = new Date(String(v).replace(" ", "T"));
    return Number.isNaN(d.getTime()) ? v : d.toLocaleString("pt-BR", {
      day: "2-digit", month: "2-digit", hour: "2-digit", minute: "2-digit",
    });
  }

  function none(msg) {
    return `<p class="sec-none">${esc(msg)}</p>`;
  }

  function setLoading(on) {
    panel.classList.toggle("is-loading", on);
    refreshBtn.classList.toggle("is-loading", on);
    refreshBtn.disabled = on;
  }

  function filtros() {
    const fd = new FormData(formFiltros);
    return {
      dias: fd.get("dias") || "7",
      sucesso: fd.get("sucesso") || "",
      motivo: fd.get("motivo") || "",
      login: fd.get("login") || "",
      page: String(currentPage),
    };
  }

  function renderMotivos(lista) {
    if (!lista.length) {
      motivosEl.innerHTML = none("Nenhuma falha no período.");
      return;
    }
    motivosEl.innerHTML = `<ul class="sec-motivo-list">${lista.map((m) =>
      `<li><span>${esc(motivoLabels[m.motivo] || m.motivo)}</span><strong>${m.total}</strong></li>`
    ).join("")}</ul>`;
  }

  function renderIps(lista) {
    document.getElementById("as-ips-count").textContent = lista.length;
    if (!lista.length) {
      ipsEl.innerHTML = none("Nenhum IP suspeito.");
      return;
    }
    ipsEl.innerHTML = `<ul class="sec-ip-list">${lista.map((i) =>
      `<li><code title="${esc(i.ip_hash)}">${esc(shortHash(i.ip_hash))}</code><span class="sec-ip-badge">${i.total}</span></li>`
    ).join("")}</ul>`;
  }

  function renderTabela(lista) {
    tbody.innerHTML = lista.length
      ? lista.map((t) => {
          const ok = Number(t.sucesso) === 1;
          return `<tr>
            <td>${fmtDate(t.criado_em)}</td>
            <td><code title="${esc(t.login_hash)}">${esc(shortHash(t.login_hash))}</code></td>
            <td><code title="${esc(t.ip_hash)}">${esc(shortHash(t.ip_hash))}</code></td>
            <td><span class="sec-status ${ok ? "sec-status--ok" : "sec-status--fail"}">${ok ? "OK" : "Falha"}</span></td>
            <td>${esc(motivoLabels[t.motivo] || t.motivo || "—")}</td>
          </tr>`;
        }).join("")
      : `<tr><td colspan="5">${none("Nenhuma tentativa encontrada.")}</td></tr>`;
  }

  function renderPages(p) {
    if (p.pages <= 1) { pagination.innerHTML = ""; return; }
    pagination.innerHTML = `
      <button type="button" data-page="${p.page - 1}" ${p.page <= 1 ? "disabled" : ""}>← Anterior</button>
      <span>${p.page} / ${p.pages}</span>
      <button type="button" data-page="${p.page + 1}" ${p.page >= p.pages ? "disabled" : ""}>Próxima →</button>`;
  }

  async function carregar() {
    setLoading(true);
    try {
      const p = filtros();
      const url = new URL(api, location.href);
      Object.entries(p).forEach(([k, v]) => { if (v) url.searchParams.set(k, v); });

      const data = await (await fetch(url, { credentials: "same-origin" })).json();
      if (!data.ok) throw new Error(data.msg || "Erro ao carregar");

      const dias = Number(p.dias);
      document.getElementById("as-resumo-periodo").textContent =
        dias === 1 ? "24h" : `${dias} dias`;

      const { total, sucessos, falhas } = data.resumo;
      document.getElementById("as-total").textContent = total.toLocaleString("pt-BR");
      document.getElementById("as-sucessos").textContent = sucessos.toLocaleString("pt-BR");
      document.getElementById("as-falhas").textContent = falhas.toLocaleString("pt-BR");
      document.getElementById("as-taxa").textContent = total > 0 ? `${Math.round(sucessos / total * 100)}%` : "—";
      document.getElementById("as-total-lista").textContent = data.paginacao.total;

      renderMotivos(data.motivos);
      renderIps(data.ips_suspeitos);
      renderTabela(data.tentativas);
      renderPages(data.paginacao);
    } finally {
      setLoading(false);
    }
  }

  formFiltros.addEventListener("submit", (e) => {
    e.preventDefault();
    currentPage = 1;
    carregar().catch((e) => alert(e.message));
  });

  pagination.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-page]");
    if (!btn || btn.disabled) return;
    currentPage = Number(btn.dataset.page);
    carregar().catch((e) => alert(e.message));
  });

  refreshBtn.addEventListener("click", () => carregar().catch((e) => alert(e.message)));
  carregar().catch((e) => alert(e.message));
});
