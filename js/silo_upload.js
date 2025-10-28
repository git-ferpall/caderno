// =====================================
// 📤 Upload de Arquivos - Silo de Dados
// =====================================

document.addEventListener("DOMContentLoaded", () => {
  const btnUpload = document.getElementById("btn-silo-arquivo");

  if (!btnUpload) return;

  btnUpload.addEventListener("click", () => {
    // Cria seletor de arquivo invisível
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*,application/pdf,text/plain";
    input.multiple = true;

    input.onchange = () => {
      if (!input.files.length) return;
      enviarArquivosSilo(input.files);
    };

    input.click();
  });
});

// =====================================
// 🚀 Função principal de upload
// =====================================
let uploadAtivo = false;

function enviarArquivosSilo(files) {
  if (uploadAtivo) {
    abrirPopup("⚠️ Aguarde", "Já há um upload em andamento.");
    return;
  }

  uploadAtivo = true;
  const overlay = document.createElement("div");
  overlay.className = "upload-popup";
  overlay.innerHTML = `
    <div class="upload-box">
      <h3>📤 Enviando arquivos...</h3>
      <div class="progress-bar-bg" style="width:100%;background:#ddd;border-radius:6px;overflow:hidden;height:20px;margin:10px 0;">
        <div class="progress-bar" style="width:0%;height:100%;background:var(--verde);transition:width 0.3s;"></div>
      </div>
      <p class="progress-txt" style="font-size:13px;color:#333;">Iniciando upload...</p>
      <button id="btnCancelarUpload" style="background:#c33;color:#fff;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;margin-top:10px;">Cancelar</button>
    </div>
  `;
  document.body.appendChild(overlay);

  const barra = overlay.querySelector(".progress-bar");
  const txt = overlay.querySelector(".progress-txt");
  const btnCancel = overlay.querySelector("#btnCancelarUpload");

  let cancelado = false;
  btnCancel.onclick = () => {
    cancelado = true;
    xhr.abort();
    uploadAtivo = false;
    overlay.remove();
    abrirPopup("🚫 Cancelado", "Envio interrompido pelo usuário.");
  };

  const file = files[0]; // (futuro: iterar se quiser múltiplos uploads)
  const fd = new FormData();
  fd.append("arquivo", file);
  fd.append("origem", "upload");
  fd.append("parent_id", window.pastaAtual || 0);

  const xhr = new XMLHttpRequest();
  xhr.open("POST", "../funcoes/silo/upload_arquivo.php");

  // 📊 Progresso visual
  xhr.upload.onprogress = (e) => {
    if (e.lengthComputable) {
      const percent = Math.round((e.loaded / e.total) * 100);
      barra.style.width = percent + "%";
      txt.textContent = `Enviando ${file.name} — ${percent}%`;
    }
  };

  // 📥 Conclusão
  xhr.onload = () => {
    uploadAtivo = false;
    if (cancelado) return;

    try {
      const j = JSON.parse(xhr.responseText);
      if (j.ok) {
        barra.style.background = "var(--verde)";
        txt.textContent = "✅ Upload concluído!";
        abrirPopup("✅ Sucesso", j.msg || "Arquivo enviado com sucesso!");
        setTimeout(() => {
          overlay.remove();
          if (typeof atualizarLista === "function") atualizarLista();
          if (typeof atualizarUso === "function") atualizarUso();
        }, 800);
      } else {
        barra.style.background = "#c33";
        txt.textContent = "❌ Erro: " + (j.err || "Falha ao enviar arquivo");
        abrirPopup("❌ Erro", j.err || "Falha ao enviar arquivo.");
        setTimeout(() => overlay.remove(), 1200);
      }
    } catch (err) {
      console.error("Erro ao interpretar resposta:", xhr.responseText);
      abrirPopup("❌ Retorno inválido", "Erro na resposta do servidor.");
      overlay.remove();
    }
  };

  // ⚠️ Erros gerais
  xhr.onerror = () => {
    uploadAtivo = false;
    if (!cancelado) {
      abrirPopup("❌ Erro", "Falha na conexão durante o upload.");
      overlay.remove();
    }
  };

  xhr.send(fd);
}
