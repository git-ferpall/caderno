/**
 * Popup de produtos cultivados — área m² / % e prévia colorida
 */
(function () {
  const CORES = ["#13b0a6", "#e91e63", "#ff9800", "#9c27b0", "#4caf50", "#2196f3", "#795548", "#607d8b"];

  let catalogoProdutos = [];
  let popupState = {
    mode: "edit",
    bancadaId: 0,
    bancadaNome: "",
    areaTotal: 0,
    estufaId: 0,
    rows: [],
    onDraftSave: null,
  };

  document.addEventListener("DOMContentLoaded", () => {
    initMiniPreviews();
    initPopup();
    carregarCatalogo();
  });

  async function carregarCatalogo() {
    try {
      const resp = await fetch("/funcoes/buscar_produtos.php", { credentials: "same-origin" });
      const data = await resp.json();
      catalogoProdutos = Array.isArray(data) ? data : [];
    } catch (err) {
      console.error("Erro ao carregar produtos:", err);
    }
  }

  function corPorIndice(i) {
    return CORES[i % CORES.length];
  }

  function fmtNum(n, dec = 2) {
    const v = Number(n) || 0;
    return v.toLocaleString("pt-BR", { minimumFractionDigits: 0, maximumFractionDigits: dec });
  }

  function enriquecerRows(rows, areaTotal) {
    const count = rows.length;
    return rows.map((r, i) => {
      let area_m2 = parseFloat(r.area_m2) || 0;
      let percentual = parseFloat(r.percentual) || 0;
      const modo = r.modo === "pct" ? "pct" : "m2";

      if (areaTotal > 0) {
        if (modo === "pct" && percentual > 0) {
          area_m2 = Math.round((areaTotal * percentual) / 100 * 100) / 100;
        } else if (modo === "m2" && area_m2 > 0) {
          percentual = Math.round((area_m2 / areaTotal) * 10000) / 100;
        } else if (area_m2 <= 0 && percentual <= 0 && count > 0) {
          percentual = Math.round((100 / count) * 100) / 100;
          area_m2 = Math.round((areaTotal / count) * 100) / 100;
        }
      } else if (percentual <= 0 && count > 0) {
        percentual = Math.round((100 / count) * 100) / 100;
      }

      return {
        produto_id: String(r.produto_id || r.id || ""),
        nome: r.nome || nomeProduto(r.produto_id || r.id),
        area_m2,
        percentual,
        modo,
        cor: r.cor || corPorIndice(i),
      };
    });
  }

  function nomeProduto(id) {
    const p = catalogoProdutos.find((x) => String(x.id) === String(id));
    return p ? p.nome : "";
  }

  function initMiniPreviews() {
    document.querySelectorAll("[data-mini-preview]").forEach((el) => {
      let produtos = [];
      try {
        produtos = JSON.parse(el.dataset.produtos || "[]");
      } catch (_) {
        produtos = [];
      }
      renderMiniPreview(el, produtos);
    });
    document.querySelectorAll("[data-culturas-bancada]").forEach((el) => {
      const wrap = el.closest(".item-bancada-cultivos-main");
      const preview = wrap?.querySelector("[data-mini-preview]");
      let produtos = [];
      try {
        produtos = JSON.parse(preview?.dataset.produtos || "[]");
      } catch (_) {
        produtos = [];
      }
      if (produtos.length) {
        renderCulturasChips(el, produtos);
      }
    });
  }

  function renderCulturasChips(container, produtos) {
    if (!container) return;
    const enriched = enriquecerRows(
      (produtos || []).map((p, i) => ({
        produto_id: p.id || p.produto_id,
        nome: p.nome,
        area_m2: p.area_m2,
        percentual: p.percentual,
        cor: p.cor || corPorIndice(i),
      })),
      0
    );

    if (!enriched.length) {
      container.innerHTML = '<span class="culturas-chip culturas-chip-vazio">Não informado</span>';
      return;
    }

    container.innerHTML = enriched
      .map((p) => {
        let meta = "";
        if (p.percentual > 0) {
          meta = `<span class="culturas-chip-meta">${fmtNum(p.percentual, 1)}%</span>`;
        } else if (p.area_m2 > 0) {
          meta = `<span class="culturas-chip-meta">${fmtNum(p.area_m2)} m²</span>`;
        }
        return `<span class="culturas-chip" style="--chip-cor:${p.cor}"><span class="culturas-chip-nome">${escapeHtml(p.nome)}</span>${meta}</span>`;
      })
      .join("");
  }

  function renderMiniPreview(container, produtos) {
    if (!container) return;
    container.innerHTML = "";
    const enriched = enriquecerRows(
      (produtos || []).map((p, i) => ({
        produto_id: p.id || p.produto_id,
        nome: p.nome,
        area_m2: p.area_m2,
        percentual: p.percentual,
        cor: p.cor || corPorIndice(i),
      })),
      0
    );

    if (!enriched.length) return;

    const bar = document.createElement("div");
    bar.className = "hidro-mini-preview-bar";
    enriched.forEach((p) => {
      const pct = p.percentual > 0 ? p.percentual : 100 / enriched.length;
      const seg = document.createElement("span");
      seg.className = "hidro-mini-preview-seg";
      seg.style.flex = String(pct);
      seg.style.background = p.cor;
      seg.title = `${p.nome} (${fmtNum(pct, 1)}%)`;
      bar.appendChild(seg);
    });
    container.appendChild(bar);
  }

  function renderPreviewBar(produtos, areaTotal) {
    const bar = document.getElementById("pc-preview-bar");
    const legend = document.getElementById("pc-preview-legend");
    if (!bar || !legend) return;

    bar.innerHTML = "";
    legend.innerHTML = "";

    const enriched = enriquecerRows(produtos, areaTotal);
    if (!enriched.length) {
      bar.innerHTML = '<div class="hidro-preview-vazio">Adicione produtos para ver a prévia</div>';
      return;
    }

    enriched.forEach((p) => {
      const pct = p.percentual > 0 ? p.percentual : 100 / enriched.length;
      const seg = document.createElement("div");
      seg.className = "hidro-preview-seg";
      seg.style.flex = String(pct);
      seg.style.background = p.cor;
      seg.title = p.nome;
      bar.appendChild(seg);

      const li = document.createElement("li");
      li.innerHTML = `<span class="hidro-legend-cor" style="background:${p.cor}"></span>
        <span class="hidro-legend-nome">${escapeHtml(p.nome)}<span class="hidro-legend-pct"> · ${fmtNum(p.percentual, 1)}%</span></span>
        <span class="hidro-legend-val">${fmtNum(p.area_m2)} m² · ${fmtNum(p.percentual, 1)}%</span>`;
      legend.appendChild(li);
    });
  }

  function escapeHtml(s) {
    return String(s || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function atualizarTotais() {
    const areaTotal = popupState.areaTotal;
    const enriched = enriquecerRows(popupState.rows, areaTotal);
    popupState.rows = enriched;

    let sumArea = 0;
    let sumPct = 0;
    enriched.forEach((r) => {
      sumArea += r.area_m2;
      sumPct += r.percentual;
    });

    const elArea = document.getElementById("pc-sum-area");
    const elPct = document.getElementById("pc-sum-pct");
    const aviso = document.getElementById("pc-aviso");
    if (elArea) elArea.textContent = fmtNum(sumArea);
    if (elPct) elPct.textContent = fmtNum(sumPct, 1);

    if (aviso) {
      if (areaTotal > 0 && Math.abs(sumArea - areaTotal) > 0.05) {
        aviso.textContent = `A soma (${fmtNum(sumArea)} m²) difere da área da bancada (${fmtNum(areaTotal)} m²).`;
        aviso.classList.remove("d-none");
      } else if (areaTotal <= 0 && Math.abs(sumPct - 100) > 0.5 && enriched.length > 0) {
        aviso.textContent = `Percentuais somam ${fmtNum(sumPct, 1)}% (ideal: 100%).`;
        aviso.classList.remove("d-none");
      } else {
        aviso.classList.add("d-none");
      }
    }

    renderPreviewBar(enriched, areaTotal);
  }

  function buildRowHtml(row, index) {
    const opts = catalogoProdutos
      .map(
        (p) =>
          `<option value="${p.id}"${String(p.id) === String(row.produto_id) ? " selected" : ""}>${escapeHtml(p.nome)}</option>`
      )
      .join("");

    const val = row.modo === "pct" ? row.percentual || "" : row.area_m2 || "";
    const modoM2 = row.modo !== "pct" ? "active" : "";
    const modoPct = row.modo === "pct" ? "active" : "";

    return `
      <div class="popup-cultivos-row" data-index="${index}">
        <div class="popup-cultivos-row-top">
          <select class="form-select form-text pc-produto" required>
            <option value="">Produto</option>
            ${opts}
          </select>
          <button type="button" class="pc-remove" title="Remover" aria-label="Remover produto">&times;</button>
        </div>
        <div class="popup-cultivos-row-area">
          <div class="pc-modo-toggle">
            <button type="button" class="pc-modo ${modoM2}" data-modo="m2">m²</button>
            <button type="button" class="pc-modo ${modoPct}" data-modo="pct">%</button>
          </div>
          <input type="number" class="form-text pc-valor" step="0.01" min="0" placeholder="${row.modo === "pct" ? "Ex: 50" : "Ex: 15"}" value="${val}">
        </div>
      </div>`;
  }

  function renderRows() {
    const container = document.getElementById("pc-rows");
    if (!container) return;
    container.innerHTML = popupState.rows.map((r, i) => buildRowHtml(r, i)).join("");
    bindRowEvents(container);
    atualizarTotais();
  }

  function bindRowEvents(container) {
    container.querySelectorAll(".pc-produto").forEach((sel) => {
      sel.addEventListener("change", onRowChange);
    });
    container.querySelectorAll(".pc-valor").forEach((inp) => {
      inp.addEventListener("input", onRowChange);
    });
    container.querySelectorAll(".pc-modo").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const rowEl = e.target.closest(".popup-cultivos-row");
        const idx = parseInt(rowEl?.dataset.index, 10);
        if (Number.isNaN(idx)) return;
        popupState.rows[idx].modo = e.target.dataset.modo;
        renderRows();
      });
    });
    container.querySelectorAll(".pc-remove").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const rowEl = e.target.closest(".popup-cultivos-row");
        const idx = parseInt(rowEl?.dataset.index, 10);
        if (Number.isNaN(idx)) return;
        popupState.rows.splice(idx, 1);
        renderRows();
      });
    });
  }

  function onRowChange() {
    const container = document.getElementById("pc-rows");
    if (!container) return;

    container.querySelectorAll(".popup-cultivos-row").forEach((rowEl, i) => {
      const produto_id = rowEl.querySelector(".pc-produto")?.value || "";
      const valor = parseFloat(rowEl.querySelector(".pc-valor")?.value) || 0;
      const modo = popupState.rows[i]?.modo === "pct" ? "pct" : "m2";

      popupState.rows[i] = {
        ...popupState.rows[i],
        produto_id,
        nome: nomeProduto(produto_id),
        modo,
        area_m2: modo === "m2" ? valor : popupState.rows[i]?.area_m2 || 0,
        percentual: modo === "pct" ? valor : popupState.rows[i]?.percentual || 0,
      };
    });

    atualizarTotais();
  }

  function lerAreaDoForm(estufaId) {
    const areaInp = document.getElementById(`b-area-estufa-${estufaId}`);
    const unidade = document.querySelector(`#add-bancada-estufa-${estufaId} [name="barea_unidade"]`);
    let area = parseFloat(areaInp?.value) || 0;
    if (unidade?.value === "ha") area *= 10000;
    return area;
  }

  function formatCulturaLabel(produtos) {
    return produtos
      .map((p) => {
        const nome = p.nome || nomeProduto(p.produto_id);
        if (p.percentual > 0) return `${nome} (${fmtNum(p.percentual, 1)}%)`;
        if (p.area_m2 > 0) return `${nome} (${fmtNum(p.area_m2)} m²)`;
        return nome;
      })
      .filter(Boolean)
      .join(", ");
  }

  function atualizarDraftUI(estufaId, produtos) {
    const resumo = document.getElementById(`cultivos-resumo-estufa-${estufaId}`);
    const preview = document.getElementById(`cultivos-preview-estufa-${estufaId}`);
    const hidden = document.getElementById(`produtos-json-estufa-${estufaId}`);

    const payload = produtos.map((p) => ({
      produto_id: parseInt(p.produto_id, 10),
      area_m2: p.area_m2,
      percentual: p.percentual,
    }));

    if (hidden) hidden.value = JSON.stringify(payload);
    if (resumo) {
      resumo.textContent = produtos.length ? formatCulturaLabel(produtos) : "Nenhum produto configurado";
    }
    renderMiniPreview(preview, produtos);
  }

  function lockBodyScroll() {
    document.documentElement.classList.add("popup-scroll-lock");
    document.body.classList.add("popup-scroll-lock");
  }

  function unlockBodyScroll() {
    document.documentElement.classList.remove("popup-scroll-lock");
    document.body.classList.remove("popup-scroll-lock");
  }

  async function abrirPopup(opts) {
    if (!catalogoProdutos.length) {
      await carregarCatalogo();
    }

    popupState = {
      mode: opts.mode || "edit",
      bancadaId: opts.bancadaId || 0,
      bancadaNome: opts.bancadaNome || "—",
      areaTotal: parseFloat(opts.areaTotal) || 0,
      estufaId: opts.estufaId || 0,
      onDraftSave: opts.onDraftSave || null,
      rows: (opts.produtos || []).map((p, i) => ({
        produto_id: String(p.id || p.produto_id || ""),
        nome: p.nome || nomeProduto(p.id || p.produto_id),
        area_m2: parseFloat(p.area_m2) || 0,
        percentual: parseFloat(p.percentual) || 0,
        modo: p.area_m2 > 0 ? "m2" : "pct",
        cor: p.cor || corPorIndice(i),
      })),
    };

    if (!popupState.rows.length) {
      popupState.rows.push({
        produto_id: "",
        nome: "",
        area_m2: 0,
        percentual: 0,
        modo: popupState.areaTotal > 0 ? "m2" : "pct",
        cor: corPorIndice(0),
      });
    }

    const nomeEl = document.getElementById("pc-bancada-nome");
    const areaEl = document.getElementById("pc-area-total");
    if (nomeEl) nomeEl.textContent = popupState.bancadaNome;
    if (areaEl) areaEl.textContent = fmtNum(popupState.areaTotal);

    renderRows();
    document.getElementById("popup-cultivos-overlay")?.classList.remove("d-none");
    lockBodyScroll();
  }

  function fecharPopup() {
    document.getElementById("popup-cultivos-overlay")?.classList.add("d-none");
    unlockBodyScroll();
  }

  async function salvarPopup() {
    onRowChange();

    const validos = popupState.rows.filter((r) => r.produto_id);
    if (!validos.length) {
      alert("Selecione ao menos um produto.");
      return;
    }

    const enriched = enriquecerRows(validos, popupState.areaTotal);

    if (popupState.mode === "draft") {
      if (popupState.onDraftSave) popupState.onDraftSave(enriched);
      fecharPopup();
      return;
    }

    const payload = enriched.map((p) => ({
      produto_id: parseInt(p.produto_id, 10),
      area_m2: p.area_m2,
      percentual: p.percentual,
    }));

    try {
      const fd = new FormData();
      fd.append("bancada_id", String(popupState.bancadaId));
      fd.append("produtos_json", JSON.stringify(payload));

      const resp = await fetch("/funcoes/atualizar_bancada_produtos.php", {
        method: "POST",
        body: fd,
        credentials: "same-origin",
      });
      const data = await resp.json();

      if (!data.ok) {
        alert(data.err || "Erro ao salvar cultivos.");
        return;
      }

      const culturasEl = document.querySelector(`[data-culturas-bancada="${popupState.bancadaId}"]`);
      if (culturasEl && data.produtos) {
        renderCulturasChips(culturasEl, data.produtos);
      }

      const mini = document.querySelector(`[data-mini-preview="${popupState.bancadaId}"]`);
      if (mini && data.produtos) {
        mini.dataset.produtos = JSON.stringify(data.produtos);
        renderMiniPreview(mini, data.produtos);
      }

      const btnEdit = document.querySelector(`.btn-edit-cultivos[data-bancada-id="${popupState.bancadaId}"]`);
      if (btnEdit && data.produtos) {
        btnEdit.dataset.produtos = JSON.stringify(data.produtos);
      }

      fecharPopup();
    } catch (err) {
      console.error(err);
      alert("Falha ao salvar. Tente novamente.");
    }
  }

  function initPopup() {
    document.addEventListener("click", (e) => {
      const btnEdit = e.target.closest(".btn-edit-cultivos");
      if (btnEdit) {
        e.preventDefault();
        let produtos = [];
        try {
          produtos = JSON.parse(btnEdit.dataset.produtos || "[]");
        } catch (_) {
          produtos = [];
        }
        abrirPopup({
          mode: "edit",
          bancadaId: parseInt(btnEdit.dataset.bancadaId, 10),
          bancadaNome: btnEdit.dataset.bancadaNome || "Bancada",
          areaTotal: parseFloat(btnEdit.dataset.areaM2) || 0,
          produtos,
        });
        return;
      }

      const btnConfig = e.target.closest(".btn-config-cultivos");
      if (btnConfig) {
        e.preventDefault();
        const estufaId = btnConfig.dataset.estufaId;
        const areaTotal = lerAreaDoForm(estufaId);
        const hidden = document.getElementById(`produtos-json-estufa-${estufaId}`);
        let produtos = [];
        if (hidden?.value) {
          try {
            const parsed = JSON.parse(hidden.value);
            produtos = parsed.map((p) => ({
              id: p.produto_id,
              area_m2: p.area_m2,
              percentual: p.percentual,
            }));
          } catch (_) {
            produtos = [];
          }
        }

        abrirPopup({
          mode: "draft",
          bancadaNome: document.getElementById(`b-nome-estufa-${estufaId}`)?.value.trim() || "Nova bancada",
          areaTotal,
          estufaId,
          produtos,
          onDraftSave: (items) => atualizarDraftUI(estufaId, items),
        });
      }
    });

    document.getElementById("pc-add")?.addEventListener("click", () => {
      popupState.rows.push({
        produto_id: "",
        nome: "",
        area_m2: 0,
        percentual: 0,
        modo: popupState.areaTotal > 0 ? "m2" : "pct",
        cor: corPorIndice(popupState.rows.length),
      });
      renderRows();
    });

    document.getElementById("pc-cancel")?.addEventListener("click", fecharPopup);
    document.getElementById("pc-save")?.addEventListener("click", salvarPopup);

    document.getElementById("popup-cultivos-overlay")?.addEventListener("click", (e) => {
      if (e.target.id === "popup-cultivos-overlay") fecharPopup();
    });
  }

  window.HidroCultivos = { abrirPopup, renderMiniPreview, formatCulturaLabel };
})();
