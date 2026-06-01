document.addEventListener("DOMContentLoaded", () => {
  const feed = document.getElementById("timeline-feed");
  const pagText = document.getElementById("timeline-page-text");
  const btnPrev = document.getElementById("timeline-prev");
  const btnNext = document.getElementById("timeline-next");
  const form = document.getElementById("timeline-filters");

  if (!feed) return;

  let pagina = 1;
  let totalPaginas = 1;

  function fmtData(iso) {
    if (!iso) return "—";
    const p = iso.split("-");
    if (p.length !== 3) return iso;
    return `${p[2]}/${p[1]}/${p[0]}`;
  }

  function escapeHtml(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function carregarTimeline() {
    const dataIni = document.getElementById("tl-data-ini")?.value || "";
    const dataFim = document.getElementById("tl-data-fim")?.value || "";
    const status = document.getElementById("tl-status")?.value || "";

    feed.innerHTML = '<div class="timeline-loading">Carregando...</div>';

    const url =
      `../funcoes/buscar_timeline.php?pagina=${pagina}&limite=20` +
      `&data_ini=${encodeURIComponent(dataIni)}` +
      `&data_fim=${encodeURIComponent(dataFim)}` +
      (status ? `&status=${encodeURIComponent(status)}` : "");

    fetch(url)
      .then((r) => r.json())
      .then((d) => {
        if (!d.ok || !d.eventos?.length) {
          feed.innerHTML = '<div class="timeline-empty">Nenhum evento encontrado neste período.</div>';
          totalPaginas = 1;
          pagText.textContent = "Página 1";
          btnPrev.disabled = true;
          btnNext.disabled = true;
          return;
        }

        totalPaginas = d.total_paginas ?? 1;
        pagText.textContent = `Página ${d.pagina} de ${totalPaginas}`;
        btnPrev.disabled = pagina <= 1;
        btnNext.disabled = pagina >= totalPaginas;

        feed.innerHTML = d.eventos
          .map((ev) => {
            const isSilo = ev.fonte === "silo";
            const cls = isSilo ? "fonte-silo" : "";
            const meta = [];
            if (ev.areas) meta.push(`<span>📍 ${escapeHtml(ev.areas)}</span>`);
            if (ev.produtos) meta.push(`<span>🌱 ${escapeHtml(ev.produtos)}</span>`);
            if (ev.quantidade) meta.push(`<span>📊 ${escapeHtml(ev.quantidade)} ${escapeHtml(ev.unidade || "")}</span>`);
            const anexos = ev.anexos > 0 ? `<span class="timeline-anexos-badge">📎 ${ev.anexos} anexo(s)</span>` : "";
            const statusCls = (ev.status || "").toLowerCase();
            const statusLabel =
              statusCls === "pendente" ? "Pendente" :
              statusCls === "concluido" ? "Concluído" :
              statusCls === "arquivo" ? "Arquivo" : ev.status;

            return `
              <article class="timeline-item ${cls}" data-fonte="${ev.fonte}" data-id="${ev.id}">
                <div class="timeline-item-top">
                  <div class="timeline-item-tipo">${ev.icone || "📋"} ${escapeHtml(ev.tipo_label)}</div>
                  <div class="timeline-item-data">${fmtData(ev.evento_em)}</div>
                </div>
                <div class="timeline-item-meta">${meta.join("")}${anexos}</div>
                ${ev.observacoes ? `<div class="timeline-item-meta" style="margin-top:6px;font-style:italic">${escapeHtml(ev.observacoes).substring(0, 120)}</div>` : ""}
                <span class="timeline-status ${statusCls}">${statusLabel}</span>
              </article>
            `;
          })
          .join("");
      })
      .catch(() => {
        feed.innerHTML = '<div class="timeline-empty">Erro ao carregar timeline.</div>';
      });
  }

  feed.addEventListener("click", (e) => {
    const item = e.target.closest(".timeline-item");
    if (!item) return;
    const fonte = item.dataset.fonte;
    const id = item.dataset.id;
    if (fonte === "apontamento" && typeof window.abrirPopupManejo === "function") {
      window.abrirPopupManejo(id);
    } else if (fonte === "silo") {
      window.location.href = "./silo";
    }
  });

  form?.addEventListener("submit", (e) => {
    e.preventDefault();
    pagina = 1;
    carregarTimeline();
  });

  btnPrev?.addEventListener("click", () => {
    if (pagina <= 1) return;
    pagina -= 1;
    carregarTimeline();
  });

  btnNext?.addEventListener("click", () => {
    if (pagina >= totalPaginas) return;
    pagina += 1;
    carregarTimeline();
  });

  // Datas padrão
  const ini = document.getElementById("tl-data-ini");
  const fim = document.getElementById("tl-data-fim");
  if (ini && !ini.value) {
    const d = new Date();
    d.setDate(d.getDate() - 90);
    ini.value = d.toISOString().slice(0, 10);
  }
  if (fim && !fim.value) {
    fim.value = new Date().toISOString().slice(0, 10);
  }

  carregarTimeline();
});
