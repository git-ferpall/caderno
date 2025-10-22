document.addEventListener("DOMContentLoaded", () => {
  const tabelaPendente = document.querySelector(".apontamento-fazer tbody");
  const tabelaConcluido = document.querySelector(".apontamento-concluido tbody");
  const countPendente = document.querySelector(".apontamento-fazer .apontamento-count");
  const countConcluido = document.querySelector(".apontamento-concluido .apontamento-count");
  const textoPendente = document.querySelector(".apontamento-fazer .nenhum-apontamento");
  const textoConcluido = document.querySelector(".apontamento-concluido .nenhum-apontamento");

  function carregarManejos() {
    fetch("../funcoes/buscar_manejos.php")
      .then(r => r.json())
      .then(data => {
        if (!data.ok) {
          console.error("Erro:", data.msg);
          return;
        }

        // Limpa tabelas
        tabelaPendente.innerHTML = "";
        tabelaConcluido.innerHTML = "";

        // === Pendentes ===
        if (data.pendentes.length > 0) {
          textoPendente.style.display = "none";
          data.pendentes.forEach(item => {
            const tr = document.createElement("tr");
            tr.dataset.id = item.id; // ⚠️ necessário pro popup
            tr.innerHTML = `
              <td>${item.data}</td>
              <td>${item.tipo}</td>
              <td>${item.areas}</td>
              <td>${item.produto}</td>
            `;
            tabelaPendente.appendChild(tr);
          });
          countPendente.textContent = data.pendentes.length;
        } else {
          textoPendente.style.display = "block";
          countPendente.textContent = "0";
        }

        // === Concluídos ===
        if (data.concluidos.length > 0) {
          textoConcluido.style.display = "none";
          data.concluidos.forEach(item => {
            const tr = document.createElement("tr");
            tr.dataset.id = item.id;
            tr.innerHTML = `
              <td>${item.data}</td>
              <td>${item.tipo}</td>
              <td>${item.areas}</td>
              <td>${item.produto}</td>
            `;
            tabelaConcluido.appendChild(tr);
          });
          countConcluido.textContent = data.concluidos.length;
        } else {
          textoConcluido.style.display = "block";
          countConcluido.textContent = "0";
        }

        // 🟢 Aplica ordenação nas duas tabelas
        inicializarOrdenacao();

        // 🟢 Inicializa popup (função do outro JS)
        if (typeof inicializarPopupLinhas === "function") {
          inicializarPopupLinhas();
        } else {
          console.warn("⚠️ Função inicializarPopupLinhas não encontrada — verifique o arquivo home_manejos_popup.js");
        }
      })
      .catch(err => console.error("Erro ao carregar manejos:", err));
  }

  // === Função de ordenação ===
  function inicializarOrdenacao() {
    const tabelas = document.querySelectorAll(".apontamento-tabela");

    tabelas.forEach(tabela => {
      const cabecalhos = tabela.querySelectorAll("th");
      cabecalhos.forEach((th, indice) => {
        th.style.cursor = "pointer";
        th.dataset.ordem = "none";

        th.addEventListener("click", () => {
          const corpo = tabela.querySelector("tbody");
          const linhas = Array.from(corpo.querySelectorAll("tr"));
          const ordemAtual = th.dataset.ordem;

          let novaOrdem = "asc";
          if (ordemAtual === "asc") novaOrdem = "desc";
          th.dataset.ordem = novaOrdem;

          cabecalhos.forEach(h => {
            if (h !== th) h.dataset.ordem = "none";
          });

          const ehData = th.id === "apt-data";

          const comparar = (a, b) => {
            const valorA = a.children[indice].innerText.trim();
            const valorB = b.children[indice].innerText.trim();

            if (ehData) {
              const [diaA, mesA, anoA] = valorA.split("/").map(Number);
              const [diaB, mesB, anoB] = valorB.split("/").map(Number);
              const dataA = new Date(anoA, mesA - 1, diaA);
              const dataB = new Date(anoB, mesB - 1, diaB);
              return novaOrdem === "asc" ? dataA - dataB : dataB - dataA;
            } else {
              return novaOrdem === "asc"
                ? valorA.localeCompare(valorB)
                : valorB.localeCompare(valorA);
            }
          };

          linhas.sort(comparar);
          corpo.innerHTML = "";
          linhas.forEach(tr => corpo.appendChild(tr));
        });
      });
    });
  }

  carregarManejos();
});
