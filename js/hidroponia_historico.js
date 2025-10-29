/**
 * HIDROPONIA_HISTORICO.JS v1.0
 * Exibe histórico de defensivos, fertilizantes e colheitas por bancada
 * Atualizado em 2025-10-29
 */

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".bancada-historico").forEach(btn => {
    btn.addEventListener("click", async () => {
      const idMatch = btn.id.match(/bancada-(.+)-estufa-(\d+)-historico$/);
      if (!idMatch) return;

      const bancadaNome = idMatch[1];
      const estufaId = idMatch[2];

      console.log(`📜 Carregando histórico da ${bancadaNome} (estufa ${estufaId})`);

      const box = document.getElementById(`e-${estufaId}-b-${bancadaNome}-historico`) ||
                  document.querySelector(`[id*="${estufaId}-b-${bancadaNome}-historico"]`);

      if (!box) {
        alert("Elemento do histórico não encontrado.");
        return;
      }

      box.classList.remove("d-none");
      box.innerHTML = "<div class='historico-loading'>Carregando histórico...</div>";

      try {
        const resp = await fetch("../funcoes/buscar_historico_hidroponia.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            estufa_id: estufaId,
            bancada_nome: bancadaNome
          })
        });

        const data = await resp.json();
        console.log("📦 Histórico recebido:", data);

        if (!data.ok || !data.historico.length) {
          box.innerHTML = "<div class='historico-none'>Nenhum registro encontrado.</div>";
          return;
        }

        let html = "<table class='table table-historico'><thead><tr><th>Data</th><th>Tipo</th><th>Descrição</th><th>Qtd</th><th>Status</th></tr></thead><tbody>";
        data.historico.forEach(item => {
          html += `
            <tr>
              <td>${item.data} ${item.hora}</td>
              <td>${item.tipo}</td>
              <td>${item.descricao}</td>
              <td>${item.quantidade}</td>
              <td>${item.status}</td>
            </tr>
          `;
        });
        html += "</tbody></table>";
        box.innerHTML = html;
      } catch (err) {
        console.error("❌ Erro ao carregar histórico:", err);
        box.innerHTML = "<div class='historico-error'>Erro ao carregar histórico.</div>";
      }
    });
  });
});
