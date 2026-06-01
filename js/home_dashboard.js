document.addEventListener("DOMContentLoaded", () => {
  const grid = document.getElementById("home-dashboard-grid");
  if (!grid) return;

  function fmtData(iso) {
    if (!iso) return "—";
    const p = iso.split("-");
    if (p.length !== 3) return iso;
    return `${p[2]}/${p[1]}/${p[0]}`;
  }

  function fmtBytes(bytes) {
    if (!bytes) return "0 B";
    const u = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return (bytes / Math.pow(1024, i)).toFixed(1) + " " + u[i];
  }

  fetch("../funcoes/buscar_dashboard.php")
    .then((r) => r.json())
    .then((d) => {
      if (!d.ok) {
        grid.innerHTML = '<p class="dash-sub" style="color:#fff">Resumo indisponível.</p>';
        return;
      }

      const siloPct = d.silo?.percentual ?? 0;
      const siloCor = siloPct >= 90 ? "#e74c3c" : siloPct >= 70 ? "#f39c12" : "var(--azul)";

      grid.innerHTML = `
        <a href="./timeline" class="home-dash-card">
          <div class="dash-value">${d.eventos_30_dias ?? 0}</div>
          <div class="dash-label">Eventos (30 dias)</div>
          <div class="dash-sub">Ver linha do tempo →</div>
        </a>
        <div class="home-dash-card ${d.atrasados > 0 ? "dash-alert" : ""}">
          <div class="dash-value">${d.atrasados ?? 0}</div>
          <div class="dash-label">Manejos atrasados</div>
          <div class="dash-sub">${d.pendentes ?? 0} pendentes no total</div>
        </div>
        <div class="home-dash-card">
          <div class="dash-value">${d.semana ?? 0}</div>
          <div class="dash-label">Para esta semana</div>
          <div class="dash-sub">Até domingo</div>
        </div>
        <div class="home-dash-card dash-ok">
          <div class="dash-value">${d.concluidos_mes ?? 0}</div>
          <div class="dash-label">Concluídos no mês</div>
        </div>
        <div class="home-dash-card">
          <div class="dash-value" style="font-size:18px">${fmtData(d.ultima_irrigacao)}</div>
          <div class="dash-label">Última irrigação</div>
        </div>
        <div class="home-dash-card">
          <div class="dash-value" style="font-size:18px">${d.ultima_aplicacao ? fmtData(d.ultima_aplicacao.data) : "—"}</div>
          <div class="dash-label">Última aplicação</div>
          <div class="dash-sub">${d.ultima_aplicacao?.tipo ?? "Nenhum registro"}</div>
        </div>
        <a href="./silo" class="home-dash-card">
          <div class="dash-value">${siloPct}%</div>
          <div class="dash-label">Silo de Dados</div>
          <div class="dash-sub">${fmtBytes(d.silo?.usado_bytes)} usados</div>
          <div class="home-dash-silo-bar"><div class="home-dash-silo-fill" style="width:${siloPct}%;background:${siloCor}"></div></div>
        </a>
      `;
    })
    .catch(() => {
      grid.innerHTML = '<p class="dash-sub" style="color:#fff">Erro ao carregar resumo.</p>';
    });
});
