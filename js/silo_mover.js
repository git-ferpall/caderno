/**
 * ============================================
 * 📦 Silo Mover - Caderno de Campo / Frutag
 * ============================================
 * Responsável por gerenciar a movimentação de arquivos/pastas
 * entre diretórios do silo de dados.
 */

console.log("✅ silo_mover.js carregado");

let moverItemID = null;

// 🧭 Abre o popup e carrega pastas disponíveis
function moverItem(id) {
  moverItemID = id;

  const popup = document.getElementById("popup-mover");
  const select = document.getElementById("mover-destino");

  popup.style.display = "flex";
  select.innerHTML = `<option value="">Carregando pastas...</option>`;

  fetch("../funcoes/silo/listar_pastas.php")
    .then(res => res.json())
    .then(data => {
      select.innerHTML = `<option value="">Selecione uma pasta</option>`;
      if (!data.ok || !Array.isArray(data.pastas)) {
        select.innerHTML = `<option value="">Nenhuma pasta disponível</option>`;
        return;
      }

      data.pastas.forEach(p => {
        const opt = document.createElement("option");
        opt.value = p.id;
        opt.textContent = p.caminho;
        select.appendChild(opt);
      });
    })
    .catch(err => {
      console.error("Erro ao listar pastas:", err);
      select.innerHTML = `<option value="">Erro ao carregar pastas</option>`;
    });
}

// 🚚 Confirma a movimentação
document.getElementById("btn-confirmar-mover")?.addEventListener("click", () => {
  const destino = document.getElementById("mover-destino").value;
  if (!destino) {
    abrirPopup("⚠️ Aviso", "Selecione uma pasta de destino antes de mover.");
    return;
  }

  const formData = new URLSearchParams();
  formData.append("id", moverItemID);
  formData.append("destino", destino);

  fetch("../funcoes/silo/mover_arquivo.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: formData.toString(),
  })
    .then(res => res.json())
    .then(r => {
      if (r.ok) {
        fecharPopupMover();
        abrirPopup("✅ Sucesso", r.msg || "Item movido com sucesso!");
        if (typeof atualizarLista === "function") atualizarLista();
      } else {
        abrirPopup("❌ Erro", r.err || "Falha ao mover item.");
      }
    })
    .catch(err => {
      console.error("Erro ao mover item:", err);
      abrirPopup("❌ Erro", "Falha na comunicação com o servidor.");
    });
});

// ❌ Fecha popup
function fecharPopupMover() {
  const popup = document.getElementById("popup-mover");
  popup.style.display = "none";
  moverItemID = null;
}

// Fecha ao clicar fora
document.addEventListener("click", (e) => {
  const popup = document.getElementById("popup-mover");
  if (!popup || popup.style.display === "none") return;

  if (e.target === popup) {
    fecharPopupMover();
  }
});
