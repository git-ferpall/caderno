/**
 * Painel de auditoria de segurança — UI moderna
 */

document.addEventListener("DOMContentLoaded", () => {
  const api = "../funcoes/admin/listar_login_attempts.php";
  const formFiltros = document.getElementById("form-filtros");
  const tbodyTentativas = document.querySelector("#tabela-tentativas tbody");
  const pagination = document.getElementById("as-pagination");
  const motivosEl = document.getElementById("sec-motivos");
  const ipsEl = document.getElementById("sec-ips");
  const refreshBtn = document.getElementById("sec-refresh");
  const cardLista = document.getElementById("card-lista");

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

  const emptyIcon = `<svg class="sec-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
  </svg>`;

  function escapeHtml(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function shortHash(hash) {
    const value = String(hash || "");
    if (value.length <= 12) return value;
    return `${value.slice(0, 8)}…${value.slice(-4)}`;
  }

  function formatDate(value) {
    if (!value) return "—";
    const d = new Date(String(value).replace(" ", "T"));
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString("pt-BR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function setLoading(on) {
    cardLista.classList.toggle("sec-loading", on);
    refreshBtn.classList.toggle("is-loading", on);
    refreshBtn.disabled = on;
  }

  function renderEmpty(message) {
    return `<div class="sec-empty">${emptyIcon}<p>${escapeHtml(message)}</p></div>`;
  }

  function getFiltros() {
    const fd = new FormData(formFiltros);
    return {
      dias: fd.get("dias") || "7",
      sucesso: fd.get("sucesso") || "",
      motivo: fd.get("motivo") || "",
      login: fd.get("login") || "",
      page: String(currentPage),
    };
  }

  function renderMotivos(motivos) {
    if (!motivos.length) {
      motivosEl.innerHTML = renderEmpty("Nenhuma falha registrada no período.");
      return;
    }

    const max = Math.max(...motivos.map((m) => Number(m.total)));
    motivosEl.innerHTML = `<div class="sec-bars">${motivos
      .map((m) => {
        const pct = max > 0 ? Math.round((Number(m.total) / max) * 100) : 0;
        const label = motivoLabels[m.motivo] || m.motivo || "—";
        const danger = ["senha_invalida", "bloqueado", "auth_fail"].includes(m.motivo);
        return `<div class="sec-bar-row">
          <div class="sec-bar-meta">
            <span class="sec-bar-label">${escapeHtml(label)}</span>
            <span class="sec-bar-count">${m.total}</span>
          </div>
          <div class="sec-bar-track">
            <div class="sec-bar-fill ${danger ? "sec-bar-fill--danger" : ""}" style="width:${pct}%"></div>
          </div>
        </div>`;
      })
      .join("")}</div>`;
  }

  function renderIps(ips) {
    document.getElementById("as-ips-count").textContent =
      ips.length === 1 ? "1 alerta" : `${ips.length} alertas`;

    if (!ips.length) {
      ipsEl.innerHTML = renderEmpty("Nenhum IP com comportamento suspeito.");
      return;
    }

    ipsEl.innerHTML = `<div class="sec-ip-list">${ips
      .map(
        (i) => `<div class="sec-ip-item">
          <code class="sec-ip-hash" title="${escapeHtml(i.ip_hash)}">${escapeHtml(shortHash(i.ip_hash))}</code>
          <span class="sec-ip-count" title="Falhas">${i.total}</span>
        </div>`
      )
      .join("")}</div>`;
  }

  function renderTentativas(tentativas) {
    tbodyTentativas.innerHTML = tentativas.length
      ? tentativas
          .map((t) => {
            const ok = Number(t.sucesso) === 1;
            return `<tr>
              <td>${formatDate(t.criado_em)}</td>
              <td><code title="${escapeHtml(t.login_hash)}">${escapeHtml(shortHash(t.login_hash))}</code></td>
              <td><code title="${escapeHtml(t.ip_hash)}">${escapeHtml(shortHash(t.ip_hash))}</code></td>
              <td><span class="sec-pill ${ok ? "sec-pill--ok" : "sec-pill--fail"}">${ok ? "Sucesso" : "Falha"}</span></td>
              <td>${escapeHtml(motivoLabels[t.motivo] || t.motivo || "—")}</td>
            </tr>`;
          })
          .join("")
      : `<tr><td colspan="5">${renderEmpty("Nenhuma tentativa encontrada.")}</td></tr>`;
  }

  function renderPagination(p) {
    if (p.pages <= 1) {
      pagination.innerHTML = "";
      return;
    }

    pagination.innerHTML = `
      <button type="button" class="sec-page-btn" data-page="${p.page - 1}" ${p.page <= 1 ? "disabled" : ""}>← Anterior</button>
      <span class="sec-page-info">Página <strong>${p.page}</strong> de <strong>${p.pages}</strong></span>
      <button type="button" class="sec-page-btn" data-page="${p.page + 1}" ${p.page >= p.pages ? "disabled" : ""}>Próxima →</button>
    `;
  }

  async function carregar() {
    setLoading(true);
    try {
      const params = getFiltros();
      const url = new URL(api, window.location.href);
      Object.entries(params).forEach(([k, v]) => {
        if (v !== "") url.searchParams.set(k, v);
      });

      const r = await fetch(url, { credentials: "same-origin" });
      const data = await r.json();
      if (!data.ok) throw new Error(data.msg || "Erro ao carregar auditoria");

      const dias = Number(params.dias);
      const periodoLabel = dias === 1 ? "Últimas 24 horas" : `Últimos ${dias} dias`;
      document.getElementById("as-resumo-periodo").textContent = periodoLabel;

      const total = data.resumo.total;
      const sucessos = data.resumo.sucessos;
      const falhas = data.resumo.falhas;
      const taxa = total > 0 ? Math.round((sucessos / total) * 100) : 0;

      document.getElementById("as-total").textContent = total.toLocaleString("pt-BR");
      document.getElementById("as-sucessos").textContent = sucessos.toLocaleString("pt-BR");
      document.getElementById("as-falhas").textContent = falhas.toLocaleString("pt-BR");
      document.getElementById("as-taxa").textContent = `${taxa}%`;
      document.getElementById("as-total-lista").textContent = `${data.paginacao.total.toLocaleString("pt-BR")} registro(s)`;

      renderMotivos(data.motivos);
      renderIps(data.ips_suspeitos);
      renderTentativas(data.tentativas);
      renderPagination(data.paginacao);
    } finally {
      setLoading(false);
    }
  }

  formFiltros.addEventListener("submit", (e) => {
    e.preventDefault();
    currentPage = 1;
    carregar().catch((err) => alert(err.message));
  });

  pagination.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-page]");
    if (!btn || btn.disabled) return;
    currentPage = Number(btn.dataset.page);
    carregar().catch((err) => alert(err.message));
  });

  refreshBtn.addEventListener("click", () => {
    carregar().catch((err) => alert(err.message));
  });

  carregar().catch((err) => alert(err.message));
});
