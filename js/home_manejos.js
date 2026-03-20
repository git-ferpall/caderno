document.addEventListener("DOMContentLoaded", () => {
  const tabelaPendente = document.querySelector(".apontamento-fazer tbody");
  const tabelaConcluido = document.querySelector(".apontamento-concluido tbody");

  const countPendente = document.querySelector(".apontamento-fazer .apontamento-count");
  const countConcluido = document.querySelector(".apontamento-concluido .apontamento-count");

  const textoPendente = document.querySelector(".apontamento-fazer .nenhum-apontamento");
  const textoConcluido = document.querySelector(".apontamento-concluido .nenhum-apontamento");

  const limite = 10;

  let pagePendente = 1;
  let pageConcluido = 1;
  let totalPaginasPendente = 1;
  let totalPaginasConcluido = 1;

  const sortState = {
    pendente: { key: "data", dir: "desc" },
    concluido: { key: "data", dir: "desc" }
  };

  const paginationUI = {
    pendente: {
      container: document.querySelector('.manejos-pagination[data-status="pendente"]'),
      btnPrev: document.querySelector('.manejos-page-btn[data-status="pendente"][data-dir="-1"]'),
      btnNext: document.querySelector('.manejos-page-btn[data-status="pendente"][data-dir="1"]'),
      text: document.querySelector('.manejos-page-text[data-status="pendente"]'),
    },
    concluido: {
      container: document.querySelector('.manejos-pagination[data-status="concluido"]'),
      btnPrev: document.querySelector('.manejos-page-btn[data-status="concluido"][data-dir="-1"]'),
      btnNext: document.querySelector('.manejos-page-btn[data-status="concluido"][data-dir="1"]'),
      text: document.querySelector('.manejos-page-text[data-status="concluido"]'),
    }
  };

  function setPagination(status, page, totalPages) {
    const ui = paginationUI[status];
    if (!ui) return;

    if (ui.text) ui.text.textContent = `Página ${page} de ${totalPages}`;

    if (ui.btnPrev) {
      ui.btnPrev.disabled = page <= 1;
      ui.btnPrev.style.opacity = page <= 1 ? 0.5 : 1;
      ui.btnPrev.style.cursor = page <= 1 ? "not-allowed" : "pointer";
    }
    if (ui.btnNext) {
      ui.btnNext.disabled = page >= totalPages;
      ui.btnNext.style.opacity = page >= totalPages ? 0.5 : 1;
      ui.btnNext.style.cursor = page >= totalPages ? "not-allowed" : "pointer";
    }

    if (ui.container) {
      ui.container.style.display = totalPages > 1 ? "flex" : "none";
    }
  }

  function carregarManejos() {
    const url = `../funcoes/buscar_manejos.php?limite=${limite}` +
      `&pendente_page=${pagePendente}&concluido_page=${pageConcluido}` +
      `&pendente_sort=${encodeURIComponent(sortState.pendente.key)}&pendente_dir=${encodeURIComponent(sortState.pendente.dir)}` +
      `&concluido_sort=${encodeURIComponent(sortState.concluido.key)}&concluido_dir=${encodeURIComponent(sortState.concluido.dir)}`;
    fetch(url)
      .then(r => r.json())
      .then(data => {
        if (!data.ok) {
          console.error("Erro:", data.msg);
          return;
        }

        // Limpa tabelas
        tabelaPendente.innerHTML = "";
        tabelaConcluido.innerHTML = "";

        // =========================
        // PENDENTES
        // =========================
        if (data.pendentes.length > 0) {
          textoPendente.style.display = "none";

          data.pendentes.forEach(item => {
            const tr = document.createElement("tr");
            tr.dataset.id = item.id; // usado no popup

            tr.innerHTML = `
              <td>${item.data}</td>
              <td>${item.tipo}</td>
              <td>${item.areas}</td>
              <td>${item.produto}</td>
            `;

            tabelaPendente.appendChild(tr);
          });

          countPendente.textContent = data.total_pendentes ?? data.pendentes.length;
        } else {
          textoPendente.style.display = "block";
          countPendente.textContent = data.total_pendentes ?? 0;
        }

        // =========================
        // CONCLUÍDOS
        // =========================
        if (data.concluidos.length > 0) {
          textoConcluido.style.display = "none";

          data.concluidos.forEach(item => {
            const tr = document.createElement("tr");
            tr.dataset.id = item.id;

            tr.innerHTML = `
              <td>${item.data}</td>
              <td>${item.conclusao ?? '—'}</td>
              <td>${item.tipo}</td>
              <td>${item.areas}</td>
              <td>${item.produto}</td>
            `;

            tabelaConcluido.appendChild(tr);
          });

          countConcluido.textContent = data.total_concluidos ?? data.concluidos.length;
        } else {
          textoConcluido.style.display = "block";
          countConcluido.textContent = data.total_concluidos ?? 0;
        }

        totalPaginasPendente = data.total_paginas_pendente ?? 1;
        totalPaginasConcluido = data.total_paginas_concluido ?? 1;

        pagePendente = data.pagina_pendente ?? pagePendente;
        pageConcluido = data.pagina_concluido ?? pageConcluido;

        setPagination("pendente", pagePendente, totalPaginasPendente);
        setPagination("concluido", pageConcluido, totalPaginasConcluido);

        // Inicializa popup (outro JS)
        if (typeof inicializarPopupLinhas === "function") {
          inicializarPopupLinhas();
        } else {
          console.warn("⚠️ Função inicializarPopupLinhas não encontrada — verifique home_manejos_popup.js");
        }
      })
      .catch(err => console.error("Erro ao carregar manejos:", err));
  }

  // =========================
  // ORDENAÇÃO DAS TABELAS (server-side)
  // =========================
  function inicializarOrdenacao() {
    const tabelas = document.querySelectorAll(".apontamento-tabela");

    tabelas.forEach(tabela => {
      const cabecalhos = tabela.querySelectorAll("th");

      cabecalhos.forEach((th, indice) => {
        th.style.cursor = "pointer";
        th.dataset.ordem = "none";

        th.addEventListener("click", () => {
          const container = tabela.closest(".apontamento-concluido") ? "concluido" : "pendente";

          // mapear id do th para chave usada no backend
          let key = null;
          switch (th.id) {
            case "apt-data":
              key = "data";
              break;
            case "apt-conclusao":
              key = "conclusao";
              break;
            case "apt-nome":
              key = "tipo";
              break;
            case "apt-area":
              key = "areas";
              break;
            case "apt-cult":
              key = "produto";
              break;
          }

          if (!key) return;

          const estado = sortState[container];
          const novaDir = (estado.key === key && estado.dir === "asc") ? "desc" : "asc";
          sortState[container] = { key, dir: novaDir };

          cabecalhos.forEach(h => {
            h.dataset.ordem = "none";
          });
          th.dataset.ordem = novaDir;

          // quando muda ordenação, volta para a primeira página para evitar “página vazia”
          if (container === "pendente") pagePendente = 1;
          if (container === "concluido") pageConcluido = 1;

          carregarManejos();
        });
      });
    });
  }

  // Ativa ordenação uma vez (não duplicar handlers a cada fetch)
  inicializarOrdenacao();

  // Pagination buttons
  paginationUI.pendente?.btnPrev?.addEventListener("click", () => {
    if (pagePendente <= 1) return;
    pagePendente -= 1;
    carregarManejos();
  });
  paginationUI.pendente?.btnNext?.addEventListener("click", () => {
    if (pagePendente >= totalPaginasPendente) return;
    pagePendente += 1;
    carregarManejos();
  });

  paginationUI.concluido?.btnPrev?.addEventListener("click", () => {
    if (pageConcluido <= 1) return;
    pageConcluido -= 1;
    carregarManejos();
  });
  paginationUI.concluido?.btnNext?.addEventListener("click", () => {
    if (pageConcluido >= totalPaginasConcluido) return;
    pageConcluido += 1;
    carregarManejos();
  });

  carregarManejos();
});
