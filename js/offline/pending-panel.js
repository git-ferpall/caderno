/**
 * Painel na home — fila offline (online e offline).
 */
const OfflinePendingPanel = (() => {
  const FALLBACK_TIPOS = {
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

  let syncing = false;

  function tipoLabels() {
    if (typeof OfflineSync !== "undefined" && OfflineSync.getTipoLabels) {
      const fromManifest = OfflineSync.getTipoLabels();
      if (Object.keys(fromManifest).length) return { ...FALLBACK_TIPOS, ...fromManifest };
    }
    return FALLBACK_TIPOS;
  }

  function tipoFromUrl(url) {
    const m = String(url).match(/salvar_([^.]+)\.php/i);
    if (!m) return "Apontamento";
    const key = `salvar_${m[1]}`;
    const labels = tipoLabels();
    if (labels[key]) return labels[key];
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

  function setHint(text) {
    const hint = document.querySelector(".offline-sync-hint");
    if (hint) hint.textContent = text;
  }

  async function runSyncNow() {
    if (syncing) return;
    const btn = document.getElementById("btn-offline-sync-now");
    if (btn) btn.disabled = true;
    syncing = true;

    if (typeof OfflineConnectivity !== "undefined") {
      const up = await OfflineConnectivity.hasServerReachable(true);
      if (!up) {
        if (typeof OfflineUI !== "undefined") {
          OfflineUI.setBanner("Sem conexão com o servidor. Tente de novo com internet.", "warn");
        }
        syncing = false;
        if (btn) btn.disabled = false;
        return;
      }
    }

    if (typeof OfflineApp !== "undefined" && OfflineApp.runSync) {
      await OfflineApp.runSync();
    } else if (typeof OfflineSync !== "undefined") {
      if (typeof OfflineUI !== "undefined") OfflineUI.setBanner("Sincronizando apontamentos...", "sync");
      await OfflineSync.syncAll();
      if (typeof OfflineUI !== "undefined") OfflineUI.hideBanner();
    }

    syncing = false;
    if (btn) btn.disabled = false;
    await refresh();
  }

  async function refresh() {
    const panel = document.getElementById("offline-sync-panel");
    const list = document.getElementById("offline-sync-list");
    const countEl = document.getElementById("offline-sync-count");
    if (!panel || !list) return;

    let items = [];
    if (typeof OfflineDB !== "undefined") {
      try {
        items = await OfflineDB.listFila();
      } catch {
        items = [];
      }
    }

    if (countEl) countEl.textContent = String(items.length);

    const badge = document.getElementById("offline-pending-badge");
    if (badge) {
      if (items.length > 0 && navigator.onLine) {
        badge.textContent =
          items.length === 1
            ? "1 apontamento aguardando sync"
            : `${items.length} apontamentos aguardando sync`;
        badge.classList.remove("d-none");
      } else {
        badge.classList.add("d-none");
      }
    }

    if (!items.length) {
      panel.classList.add("d-none");
      panel.setAttribute("aria-hidden", "true");
      list.innerHTML = "";
      return;
    }

    panel.classList.remove("d-none");
    panel.setAttribute("aria-hidden", "false");

    if (navigator.onLine) {
      setHint("Com internet: use «Sincronizar agora» para enviar ao servidor.");
    } else {
      setHint("Salvos neste aparelho. Serão enviados quando houver internet.");
    }

    list.innerHTML = items
      .map((item) => {
        const tipo = tipoFromUrl(item.url);
        const data = fmtData(item.criadoEm, item.body);
        const extra = resumoBody(item.body);
        const tent = item.tentativas ? ` · ${item.tentativas} tentativa(s)` : "";
        const errHint = item.lastError
          ? `<span class="offline-sync-item-meta offline-sync-item-err">${item.lastError}${tent}</span>`
          : tent
            ? `<span class="offline-sync-item-meta">${tent.replace(/^ · /, "")}</span>`
            : "";
        return `<li class="offline-sync-item" data-queue-id="${item.id}">
          <div class="offline-sync-item-main">
            <strong class="offline-sync-item-tipo">${tipo}</strong>
            <span class="offline-sync-item-data">${data}</span>
          </div>
          ${extra ? `<span class="offline-sync-item-meta">${extra}</span>` : ""}
          ${errHint}
          <button type="button" class="offline-sync-remove" data-remove-id="${item.id}" title="Remover da fila">×</button>
        </li>`;
      })
      .join("");
  }

  function bindEvents() {
    document.getElementById("btn-offline-sync-now")?.addEventListener("click", () => runSyncNow());

    document.getElementById("offline-sync-list")?.addEventListener("click", async (e) => {
      const btn = e.target.closest("[data-remove-id]");
      if (!btn) return;
      const id = btn.getAttribute("data-remove-id");
      if (!id) return;
      if (!confirm("Remover este apontamento da fila local? Ele não será enviado ao servidor.")) return;
      if (typeof OfflineSync !== "undefined" && OfflineSync.removeFromQueue) {
        await OfflineSync.removeFromQueue(id);
      } else if (typeof OfflineDB !== "undefined") {
        await OfflineDB.removeFila(id);
      }
      await refresh();
      if (typeof OfflineUI !== "undefined") OfflineUI.updateBadge(await OfflineDB.countFila());
    });

    window.addEventListener("offline", () => refresh());
    window.addEventListener("online", () => refresh());
    document.addEventListener("DOMContentLoaded", () => refresh());
  }

  bindEvents();

  return { refresh, runSyncNow };
})();

window.OfflinePendingPanel = OfflinePendingPanel;
