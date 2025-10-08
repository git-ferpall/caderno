document.addEventListener("DOMContentLoaded", () => {
  const tabelaPendente = document.querySelector(".apontamento-fazer tbody");
  const tabelaConcluido = document.querySelector(".apontamento-concluido tbody");
  const countPendente = document.querySelector(".apontamento-fazer .apontamento-count");
  const countConcluido = document.querySelector(".apontamento-concluido .apontamento-count");
  const textoPendente = document.querySelector(".apontamento-fazer .nenhum-apontamento");
  const textoConcluido = document.querySelector(".apontamento-concluido .nenhum-apontamento");

  // Função principal
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
            tr.innerHTML = `
              <td>${item.data}</td>
              <td>${item.tipo}</td>
              <td>${item.areas}</td>
              <td>—</td>
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
            tr.innerHTML = `
              <td>${item.data}</td>
              <td>${item.tipo}</td>
              <td>${item.areas}</td>
              <td>—</td>
            `;
            tabelaConcluido.appendChild(tr);
          });
          countConcluido.textContent = data.concluidos.length;
        } else {
          textoConcluido.style.display = "block";
          countConcluido.textContent = "0";
        }
      })
      .catch(err => console.error("Erro ao carregar manejos:", err));
  }

  carregarManejos();
});
