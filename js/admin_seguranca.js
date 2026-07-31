/**
 * Painel de auditoria de segurança (tentativas de login).
 */

document.addEventListener("DOMContentLoaded", () => {
  const api = "../funcoes/admin/listar_login_attempts.php";
  const formFiltros = document.getElementById("form-filtros");
  const tbodyTentativas = document.querySelector("#tabela-tentativas tbody");
  const tbodyMotivos = document.querySelector("#tabela-motivos tbody");
  const tbodyIps = document.querySelector("#tabela-ips tbody");
  const pagination = document.getElementById("as-pagination");

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
    return d.toLocaleString("pt-BR");
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

  async function carregar() {
    const params = getFiltros();
    const url = new URL(api, window.location.href);
    Object.entries(params).forEach(([k, v]) => {
      if (v !== "") url.searchParams.set(k, v);
    });

    const r = await fetch(url, { credentials: "same-origin" });
    const data = await r.json();
    if (!data.ok) throw new Error(data.msg || "Erro ao carregar auditoria");

    const dias = Number(params.dias);
    document.getElementById("as-resumo-periodo").textContent =
      dias === 1 ? "Últimas 24 horas" : `Últimos ${dias} dias`;

    document.getElementById("as-total").textContent = data.resumo.total;
    document.getElementById("as-sucessos").textContent = data.resumo.sucessos;
    document.getElementById("as-falhas").textContent = data.resumo.falhas;
    document.getElementById("as-total-lista").textContent = `${data.paginacao.total} registro(s)`;

    tbodyMotivos.innerHTML = data.motivos.length
      ? data.motivos
          .map(
            (m) => `<tr>
              <td>${escapeHtml(motivoLabels[m.motivo] || m.motivo || "—")}</td>
              <td><strong>${m.total}</strong></td>
            </tr>`
          )
          .join("")
      : `<tr><td colspan="2" class="as-empty">Nenhuma falha no período.</td></tr>`;

    tbodyIps.innerHTML = data.ips_suspeitos.length
      ? data.ips_suspeitos
          .map(
            (i) => `<tr>
              <td><code title="${escapeHtml(i.ip_hash)}">${escapeHtml(shortHash(i.ip_hash))}</code></td>
              <td><strong>${i.total}</strong></td>
            </tr>`
          )
          .join("")
      : `<tr><td colspan="2" class="as-empty">Nenhum IP suspeito no período.</td></tr>`;

    tbodyTentativas.innerHTML = data.tentativas.length
      ? data.tentativas
          .map((t) => {
            const ok = Number(t.sucesso) === 1;
            return `<tr>
              <td>${formatDate(t.criado_em)}</td>
              <td><code title="${escapeHtml(t.login_hash)}">${escapeHtml(shortHash(t.login_hash))}</code></td>
              <td><code title="${escapeHtml(t.ip_hash)}">${escapeHtml(shortHash(t.ip_hash))}</code></td>
              <td><span class="au-badge ${ok ? "au-badge-local" : "au-badge-frutag"}">${ok ? "Sucesso" : "Falha"}</span></td>
              <td>${escapeHtml(motivoLabels[t.motivo] || t.motivo || "—")}</td>
            </tr>`;
          })
          .join("")
      : `<tr><td colspan="5" class="as-empty">Nenhuma tentativa encontrada.</td></tr>`;

    renderPagination(data.paginacao);
  }

  function renderPagination(p) {
    if (p.pages <= 1) {
      pagination.innerHTML = "";
      return;
    }

    const buttons = [];
    if (p.page > 1) {
      buttons.push(`<button type="button" data-page="${p.page - 1}">Anterior</button>`);
    }
    buttons.push(`<span class="as-page-info">Página ${p.page} de ${p.pages}</span>`);
    if (p.page < p.pages) {
      buttons.push(`<button type="button" data-page="${p.page + 1}">Próxima</button>`);
    }
    pagination.innerHTML = buttons.join("");
  }

  formFiltros.addEventListener("submit", (e) => {
    e.preventDefault();
    currentPage = 1;
    carregar().catch((err) => alert(err.message));
  });

  pagination.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-page]");
    if (!btn) return;
    currentPage = Number(btn.dataset.page);
    carregar().catch((err) => alert(err.message));
  });

  carregar().catch((err) => alert(err.message));
});
