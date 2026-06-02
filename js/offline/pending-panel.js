/**
 * Painel na home — apontamentos na fila offline (só visível sem internet).
 */
const OfflinePendingPanel = (() => {
  const TIPO_LABELS = {
    salvar_plantio: "Plantio",
    salvar_transplantio: "Transplantio",
    salvar_colheita: "Colheita",
    salvar_clima: "Climático",
    salvar_fertilizante: "Fertilizante",
    salvar_herbicida: "Herbicida",
    salvar_fungicida: "Fungicida",
    salvar_inseticida: "Inseticida",
    salvar_adubacao_calcario: "Adubação (calcário)",
    salvar_adubacao_organica: "Adubação orgânica",
    salvar_irrigacao: "Irrigação",
    salvar_controle_agua: "Controle de água",
    salvar_moscas_frutas: "Mosca-das-frutas",
    salvar_pragas_doencas: "Pragas e doenças",
    salvar_manejo_integrado: "Manejo integrado",
    salvar_erradicacao: "Erradicação",
    salvar_revisao_maquinas: "Revisão de máquinas",
    salvar_coleta_analise: "Coleta e análise",
    salvar_visita_tecnica: "Visita técnica",
    salvar_personalizado: "Personalizado",
    salvar_colheita_hidroponia: "Colheita hidroponia",
    salvar_fertilizante_hidroponia: "Fertilizante hidroponia",
    salvar_defensivo_hidroponia: "Defensivo hidroponia",
    salvar_estufa: "Hidroponia (estufa)",
    salvar_bancada: "Hidroponia (bancada)",
  };

  function tipoFromUrl(url) {
    const m = String(url).match(/salvar_([^.]+)\.php/i);
    if (!m) return "Apontamento";
    const key = `salvar_${m[1]}`;
    if (TIPO_LABELS[key]) return TIPO_LABELS[key];
    return m[1].replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
  }

  function fmtData(ts, body) {
    const raw = body?.data || body?.data_apontamento;
    if (raw) {
      const p = String(raw).split("-");
      if (p.length === 3) return `${p[2]}/${p[1]}/${p[0]}`;
      return raw;
    }
    if (!ts) return "—";
    const d = new Date(ts);
    return d.toLocaleDateString("pt-BR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function resumoBody(body) {
    if (!body || typeof body !== "object") return "";
    const parts = [];
    if (body.area) parts.push(`Área: ${body.area}`);
    if (body.area_origem) parts.push(`Origem: ${body.area_origem}`);
    if (body.area_destino) parts.push(`Destino: ${body.area_destino}`);
    if (body.produto) parts.push(`Produto: ${body.produto}`);
    if (Array.isArray(body.area) && body.area.length) parts.push(`${body.area.length} área(s)`);
    return parts.slice(0, 2).join(" · ");
  }

  function shouldShowPanel() {
    return !navigator.onLine;
  }

  async function refresh() {
    const panel = document.getElementById("offline-sync-panel");
    const list = document.getElementById("offline-sync-list");
    const countEl = document.getElementById("offline-sync-count");
    if (!panel || !list) return;

    const badge = document.getElementById("offline-pending-badge");
    if (badge) badge.classList.add("d-none");

    if (!shouldShowPanel()) {
      panel.classList.add("d-none");
      panel.setAttribute("aria-hidden", "true");
      return;
    }

    let items = [];
    if (typeof OfflineDB !== "undefined") {
      try {
        items = await OfflineDB.listFila();
      } catch {
        items = [];
      }
    }

    if (countEl) countEl.textContent = String(items.length);

    if (!items.length) {
      panel.classList.add("d-none");
      panel.setAttribute("aria-hidden", "true");
      list.innerHTML = "";
      return;
    }

    panel.classList.remove("d-none");
    panel.setAttribute("aria-hidden", "false");

    list.innerHTML = items
      .map((item) => {
        const tipo = tipoFromUrl(item.url);
        const data = fmtData(item.criadoEm, item.body);
        const extra = resumoBody(item.body);
        return `<li class="offline-sync-item">
          <div class="offline-sync-item-main">
            <strong class="offline-sync-item-tipo">${tipo}</strong>
            <span class="offline-sync-item-data">${data}</span>
          </div>
          ${extra ? `<span class="offline-sync-item-meta">${extra}</span>` : ""}
        </li>`;
      })
      .join("");
  }

  function bindEvents() {
    window.addEventListener("offline", () => refresh());
    window.addEventListener("online", () => refresh());
    document.addEventListener("DOMContentLoaded", () => refresh());
  }

  bindEvents();

  return { refresh, shouldShowPanel };
})();

window.OfflinePendingPanel = OfflinePendingPanel;
