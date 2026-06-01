document.addEventListener("DOMContentLoaded", () => {
  const grid = document.getElementById("home-dashboard-grid");
  const carouselWrap = document.querySelector(".home-dashboard-carousel-wrap");
  const carousel = document.getElementById("home-dashboard-carousel");
  const dotsEl = document.getElementById("home-dashboard-dots");
  if (!grid) return;

  const carouselMq = window.matchMedia("(max-width: 768px)");
  let carouselScrollHandler = null;

  function initMobileCarousel() {
    if (carouselScrollHandler && carousel) {
      carousel.removeEventListener("scroll", carouselScrollHandler);
      carouselScrollHandler = null;
    }
    if (!carouselWrap || !carousel || !dotsEl) return;

    dotsEl.innerHTML = "";
    carouselWrap.classList.remove("has-scrolled", "is-carousel");

    if (!carouselMq.matches) return;

    const cards = grid.querySelectorAll(".home-stat:not(.home-stat-loading)");
    if (cards.length <= 1) return;

    carouselWrap.classList.add("is-carousel");

    cards.forEach((card, index) => {
      const dot = document.createElement("button");
      dot.type = "button";
      dot.className = "home-dashboard-dot" + (index === 0 ? " is-active" : "");
      dot.setAttribute("role", "tab");
      dot.setAttribute("aria-label", `Resumo ${index + 1} de ${cards.length}`);
      dot.setAttribute("aria-selected", index === 0 ? "true" : "false");
      dot.addEventListener("click", () => {
        card.scrollIntoView({ behavior: "smooth", inline: "center", block: "nearest" });
      });
      dotsEl.appendChild(dot);
    });

    function updateDots() {
      const dots = dotsEl.querySelectorAll(".home-dashboard-dot");
      if (!dots.length) return;

      const center = carousel.scrollLeft + carousel.clientWidth / 2;
      let active = 0;
      let minDist = Infinity;

      cards.forEach((card, index) => {
        const cardCenter = card.offsetLeft + card.offsetWidth / 2;
        const dist = Math.abs(cardCenter - center);
        if (dist < minDist) {
          minDist = dist;
          active = index;
        }
      });

      dots.forEach((dot, index) => {
        const isActive = index === active;
        dot.classList.toggle("is-active", isActive);
        dot.setAttribute("aria-selected", isActive ? "true" : "false");
      });
    }

    carouselScrollHandler = () => {
      carouselWrap.classList.add("has-scrolled");
      updateDots();
    };

    carousel.addEventListener("scroll", carouselScrollHandler, { passive: true });
    updateDots();
  }

  carouselMq.addEventListener("change", initMobileCarousel);

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

  function statCard({ href, emoji, value, label, sub, extraClass = "", valueClass = "" }) {
    const tag = href ? "a" : "div";
    return `
      <${tag}${href ? ` href="${href}"` : ""} class="home-stat ${extraClass}">
        <div class="home-stat-head">
          <span class="home-stat-emoji">${emoji}</span>
          <span class="home-stat-value ${valueClass}">${value}</span>
        </div>
        <span class="home-stat-label">${label}</span>
        ${sub ? `<span class="home-stat-sub">${sub}</span>` : ""}
      </${tag}>
    `;
  }

  fetch("../funcoes/buscar_dashboard.php")
    .then((r) => r.json())
    .then((d) => {
      if (!d.ok) {
        grid.innerHTML = '<div class="home-stat"><span class="home-stat-label">Resumo indisponível</span></div>';
        initMobileCarousel();
        return;
      }

      const siloPct = d.silo?.percentual ?? 0;
      const siloCor = siloPct >= 90 ? "#e74c3c" : siloPct >= 70 ? "#f39c12" : "var(--azul)";

      grid.innerHTML =
        statCard({
          href: "./timeline",
          emoji: "📅",
          value: d.eventos_30_dias ?? 0,
          label: "Eventos (30 dias)",
          sub: "Ver histórico",
        }) +
        statCard({
          emoji: "⚠️",
          value: d.atrasados ?? 0,
          label: "Atrasados",
          sub: `${d.pendentes ?? 0} pendentes`,
          extraClass: d.atrasados > 0 ? "is-alert" : "",
        }) +
        statCard({
          emoji: "📋",
          value: d.semana ?? 0,
          label: "Esta semana",
          sub: "Até domingo",
        }) +
        statCard({
          emoji: "✅",
          value: d.concluidos_mes ?? 0,
          label: "Concluídos no mês",
          extraClass: "is-ok",
        }) +
        statCard({
          emoji: "💧",
          value: fmtData(d.ultima_irrigacao),
          label: "Última irrigação",
          valueClass: "is-date",
        }) +
        statCard({
          emoji: "🧴",
          value: d.ultima_aplicacao ? fmtData(d.ultima_aplicacao.data) : "—",
          label: "Última aplicação",
          sub: d.ultima_aplicacao?.tipo ?? "Sem registro",
          valueClass: "is-date",
        }) +
        `<a href="./silo" class="home-stat">
          <div class="home-stat-head">
            <span class="home-stat-emoji">📦</span>
            <span class="home-stat-value">${siloPct}%</span>
          </div>
          <span class="home-stat-label">Silo de dados</span>
          <span class="home-stat-sub">${fmtBytes(d.silo?.usado_bytes)} usados</span>
          <div class="home-stat-bar"><div class="home-stat-bar-fill" style="width:${siloPct}%;background:${siloCor}"></div></div>
        </a>`;

      initMobileCarousel();
    })
    .catch(() => {
      grid.innerHTML = '<div class="home-stat"><span class="home-stat-label">Erro ao carregar resumo</span></div>';
      initMobileCarousel();
    });
});
