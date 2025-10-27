// ===================================
// 📦 Funções de Mover Arquivos - Silo de Dados
// ===================================

async function moverItem(id) {
  try {
    // Cria o overlay (popup)
    const overlay = document.createElement('div');
    overlay.className = 'upload-popup';
    overlay.innerHTML = `
      <div class="upload-box">
        <h3>📂 Mover item</h3>
        <p>Selecione a pasta de destino:</p>
        <select id="moverDestino" style="width:100%; padding:6px; border-radius:6px; border:1px solid #ccc; margin-top:10px;">
          <option value="0">📁 Raiz</option>
        </select>
        <div style="margin-top:15px;">
          <button id="btnMoverConfirmar" style="background:var(--verde);color:white;border:none;padding:8px 14px;border-radius:6px;cursor:pointer;">Mover</button>
          <button id="btnMoverCancelar" style="background:#ccc;color:#333;border:none;padding:8px 14px;border-radius:6px;cursor:pointer;">Cancelar</button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);

    // ===========================
    // 📂 Busca lista de pastas disponíveis
    // ===========================
    const res = await fetch("../funcoes/silo/listar_arquivos.php?parent_id=0");
    const j = await res.json();

    if (j.ok && Array.isArray(j.arquivos)) {
      const select = overlay.querySelector("#moverDestino");

      j.arquivos
        .filter(a => a.is_folder)
        .forEach(pasta => {
          const opt = document.createElement("option");
          opt.value = pasta.caminho_arquivo || pasta.nome_arquivo;
          opt.textContent = "📁 " + pasta.nome_arquivo;
          select.appendChild(opt);
        });
    }

    // ===========================
    // ❌ Cancelar
    // ===========================
    overlay.querySelector("#btnMoverCancelar").onclick = () => overlay.remove();

    // ===========================
    // ✅ Confirmar
    // ===========================
    overlay.querySelector("#btnMoverConfirmar").onclick = async () => {
      const destino = overlay.querySelector("#moverDestino").value;

      const fd = new FormData();
      fd.append("id", id);
      fd.append("destino", destino);

      try {
        const res = await fetch("../funcoes/silo/mover_arquivo.php", {
          method: "POST",
          body: fd,
        });

        const text = await res.text();
        console.log("📦 Retorno mover_arquivo.php:", text);

        let j;
        try {
          j = JSON.parse(text);
        } catch {
          abrirPopup("❌ Erro", "Resposta inválida do servidor.");
          overlay.remove();
          return;
        }

        if (j.ok) {
          abrirPopup("📦 Sucesso", j.msg || "Item movido com sucesso!");
          overlay.remove();

          // ===========================
          // 🔄 Atualiza visual após mover
          // ===========================
          setTimeout(async () => {
            const pastaAtual = window.siloPastaAtual || "0";
            const pastaDestino = (j.destino || "0")
              .replace(/^silo\/\d+\//, "")
              .trim();

            console.log("🌿 Atualização → atual:", pastaAtual, "| destino:", pastaDestino);

            // Atualiza a pasta atual
            if (typeof atualizarLista === "function") {
              await atualizarLista();
            }

            // Se o destino for a pasta aberta, atualiza também
            if (pastaAtual === pastaDestino && typeof atualizarLista === "function") {
              console.log("🔄 Atualizando pasta destino...");
              await atualizarLista();
            }
          }, 300);
        } else {
          abrirPopup("❌ Erro", j.err || "Falha ao mover o item.");
        }
      } catch (err) {
        console.error("Erro ao mover item:", err);
        abrirPopup("❌ Erro", "Falha inesperada ao tentar mover o item.");
      }
    };
  } catch (err) {
    console.error("Erro ao mover item:", err);
    abrirPopup("❌ Erro", "Erro inesperado ao abrir a janela de mover.");
  }
}

window.moverItem = moverItem;
