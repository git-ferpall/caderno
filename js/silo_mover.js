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
        <h3>📂 Mover arquivo</h3>
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

    // Preenche o select com pastas do usuário
    const res = await fetch("../funcoes/silo/listar_arquivos.php");
    const j = await res.json();

    if (j.ok && Array.isArray(j.arquivos)) {
      const select = overlay.querySelector("#moverDestino");

      // apenas pastas
      j.arquivos
        .filter(a => a.tipo === 'pasta' || a.tipo_arquivo === 'folder')
        .forEach(pasta => {
          const opt = document.createElement("option");
          opt.value = pasta.caminho_arquivo || pasta.nome_arquivo;
          opt.textContent = "📁 " + pasta.nome_arquivo;
          select.appendChild(opt);
        });
    }

    // Evento de cancelar
    overlay.querySelector("#btnMoverCancelar").onclick = () => overlay.remove();

    // Evento de confirmar
    overlay.querySelector("#btnMoverConfirmar").onclick = async () => {
      const destino = overlay.querySelector("#moverDestino").value;

      const fd = new FormData();
      fd.append("id", id);
      fd.append("destino", destino);

      const res = await fetch("../funcoes/silo/mover_arquivo.php", {
        method: "POST",
        body: fd,
      });

      const text = await res.text();
      console.log("📦 Retorno mover_arquivo.php:", text);

      let j;
      try {
        j = JSON.parse(text);
      } catch (err) {
        abrirPopup("❌ Erro", "Resposta inválida do servidor.");
        overlay.remove();
        return;
      }

      if (j.ok) {
        abrirPopup("📦 Sucesso", j.msg || "Item movido com sucesso!");
        overlay.remove();
        await atualizarLista(); // 🔁 atualiza automaticamente
      } else {
        abrirPopup("❌ Erro", j.err || "Falha ao mover o item.");
      }
    };
  } catch (err) {
    console.error("Erro ao mover item:", err);
    abrirPopup("❌ Erro", "Falha inesperada ao tentar mover o item.");
  }
}
window.moverItem = moverItem;
