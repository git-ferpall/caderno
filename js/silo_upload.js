// ===================================
// 📤 Upload de Arquivos com Barra de Progresso
// ===================================

let xhrAtivo = null; // referência global para cancelar upload

async function enviarArquivosSilo(arquivos, parent_id = "") {
  if (!arquivos || arquivos.length === 0) return;

  // Mostra popup
  const popup = document.getElementById("uploadPopup");
  const lista = document.getElementById("uploadLista");
  const resumo = document.getElementById("uploadResumo");
  const btnCancelar = document.getElementById("btnCancelarUpload");

  popup.style.display = "flex";
  lista.innerHTML = "";
  resumo.textContent = "Iniciando uploads...";

  // Cancela upload ativo
  if (xhrAtivo) {
    try { xhrAtivo.abort(); } catch (e) {}
  }

  // Listener do botão "Cancelar"
  btnCancelar.onclick = () => {
    if (xhrAtivo) {
      xhrAtivo.abort();
      resumo.textContent = "❌ Upload cancelado.";
      popup.style.display = "none";
    }
  };

  // Processa cada arquivo individualmente
  for (const arquivo of arquivos) {
    const item = document.createElement("div");
    item.className = "upload-item";
    item.innerHTML = `
      <div class="nome">${arquivo.name}</div>
      <div class="progress-bar"><div class="progress-fill"></div></div>
      <div class="progress-text">0%</div>
    `;
    lista.appendChild(item);

    const barra = item.querySelector(".progress-fill");
    const texto = item.querySelector(".progress-text");

    const fd = new FormData();
    fd.append("arquivo", arquivo);
    fd.append("parent_id", parent_id || "");
    fd.append("origem", "upload");

    // Faz upload via XMLHttpRequest para capturar progresso
    await new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhrAtivo = xhr;

      xhr.open("POST", "../funcoes/silo/upload_arquivo.php", true);

      // Atualiza a barra de progresso
      xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
          const percent = Math.round((e.loaded / e.total) * 100);
          barra.style.width = percent + "%";
          texto.textContent = percent + "%";
        }
      };

      xhr.onload = () => {
        if (xhr.status === 200) {
          try {
            const j = JSON.parse(xhr.responseText);
            if (j.ok) {
              texto.textContent = "✅ Concluído";
              barra.style.background = "#4caf50";
              resolve();
            } else {
              texto.textContent = "❌ " + (j.err || "Erro no envio");
              barra.style.background = "#e74c3c";
              reject();
            }
          } catch {
            texto.textContent = "❌ Resposta inválida";
            barra.style.background = "#e74c3c";
            reject();
          }
        } else {
          texto.textContent = "❌ Falha no envio (" + xhr.status + ")";
          barra.style.background = "#e74c3c";
          reject();
        }
      };

      xhr.onerror = () => {
        texto.textContent = "❌ Erro de rede";
        barra.style.background = "#e74c3c";
        reject();
      };

      xhr.send(fd);
    });
  }

  resumo.textContent = "✅ Todos os uploads concluídos!";
  setTimeout(() => {
    popup.style.display = "none";
    if (typeof atualizarLista === "function") atualizarLista();
  }, 1500);
}

// ===================================
// 🔗 Conecta botão e input automaticamente
// ===================================

document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("btn-silo-arquivo");
  const input = document.getElementById("inputUploadSilo");

  if (btn && input) {
    btn.addEventListener("click", () => input.click());
    input.addEventListener("change", function () {
      if (this.files.length > 0) {
        enviarArquivosSilo(this.files, window.pastaAtual || "");
      }
    });
  }
});
