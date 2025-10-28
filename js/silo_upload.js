// =====================================
// 📤 Upload de Arquivos - Silo de Dados
// =====================================

// 🔧 Garante que pastaAtual exista mesmo se o silo.js ainda não tiver carregado
if (typeof window.pastaAtual === "undefined") {
  const ultima = localStorage.getItem("silo_pastaAtual");
  window.pastaAtual = ultima ? parseInt(ultima) : 0;
}

document.addEventListener("DOMContentLoaded", () => {
  const btnUpload = document.getElementById("btn-silo-arquivo");
  if (!btnUpload) return;

  btnUpload.addEventListener("click", () => {
    const input = document.createElement("input");
    input.type = "file";
    input.multiple = true;
    input.accept = "image/*,application/pdf,text/plain";
    input.onchange = () => {
      if (input.files.length > 0) enviarArquivosSilo(input.files);
    };
    input.click();
  });
});

let uploadAtivo = false;

// =====================================
// 🚀 Função principal de upload
// =====================================
function enviarArquivosSilo(files) {
  if (uploadAtivo) {
    abrirPopup("⚠️ Aguarde", "Já há um upload em andamento.");
    return;
  }

  // 🚫 Verifica se há arquivos maliciosos ou suspeitos
  const tiposPermitidos = [
    "image/jpeg", "image/png", "application/pdf", "text/plain"
  ];
  for (let f of files) {
    if (!tiposPermitidos.includes(f.type)) {
      abrirPopup("❌ Tipo inválido", `O arquivo "${f.name}" possui tipo não permitido.`);
      return;
    }
  }

  uploadAtivo = true;
  const overlay = document.createElement("div");
  overlay.className = "upload-popup";
  overlay.innerHTML = `
    <div class="upload-box">
      <h3>📤 Enviando arquivo...</h3>
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

  const file = files[0]; // (upload único por vez)
  const fd = new FormData();
  fd.append("arquivo", file);
  fd.append("origem", "upload");
  fd.append("parent_id", window.pastaAtual || 0);

  console.log("📁 Enviando para pasta:", window.pastaAtual);

  const xhr = new XMLHttpRequest();
  xhr.open("POST", "../funcoes/silo/upload_arquivo.php");

  let cancelado = false;
  btnCancel.onclick = () => {
    cancelado = true;
    xhr.abort();
    uploadAtivo = false;
    overlay.remove();
    abrirPopup("🚫 Cancelado", "Envio interrompido.");
  };

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
        abrirPopup("✅ Sucesso", j.msg);
        setTimeout(() => {
          overlay.remove();
          atualizarLista();
          atualizarUso();
        }, 800);
      } else {
        barra.style.background = "#c33";
        txt.textContent = "❌ " + j.err;
        abrirPopup("❌ Erro", j.err);
        setTimeout(() => overlay.remove(), 1200);
      }
    } catch (err) {
      console.error("Erro na resposta:", xhr.responseText);
      abrirPopup("❌ Retorno inválido", "Erro ao interpretar resposta do servidor.");
      overlay.remove();
    }
  };

  // ⚠️ Erros gerais
  xhr.onerror = () => {
    uploadAtivo = false;
    if (!cancelado) {
      abrirPopup("❌ Erro", "Falha de conexão.");
      overlay.remove();
    }
  };

  xhr.send(fd);
}
