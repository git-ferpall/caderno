// ===================================
// 📊 Uso do Silo - Barra de Armazenamento
// ===================================

async function carregarUsoSilo() {
  try {
    const res = await fetch("../funcoes/silo/get_uso.php", {
      credentials: "include"
    });

    const data = await res.json();

    if (!data.ok) {
      console.warn("Erro ao obter uso do silo:", data.err);
      return;
    }

    const usado = Number(data.usado_bytes) || 0;
    const limite = Number(data.limite_bytes) || 0;
    const percentual = Number(data.percentual) || 0;

    const txt = document.getElementById("silo-uso-txt");
    const bar = document.getElementById("silo-uso-bar");
    const circle = document.querySelector(".silo-uso-circular");
    const percentLabel = document.getElementById("silo-uso-percent");

    if (!txt) return;

    const restante = limite - usado;

    txt.innerHTML = `
      <strong>${formatBytes(usado)}</strong> usados de 
      <strong>${formatBytes(limite)}</strong><br>
      <span style="opacity:0.7;">
        ${formatBytes(restante)} disponíveis (${percentual}% utilizado)
      </span>
    `;

    // ===============================
    // 📊 BARRA LINEAR (se existir)
    // ===============================
    if (bar) {
      bar.style.width = percentual + "%";

      if (percentual >= 90) {
        bar.style.backgroundColor = "#e74c3c";
      } else if (percentual >= 70) {
        bar.style.backgroundColor = "#f39c12";
      } else {
        bar.style.backgroundColor = "#2ecc71";
      }
    }

    // ===============================
    // ⭕ CÍRCULO (se existir)
    // ===============================
    if (circle && percentLabel) {
      const graus = (percentual / 100) * 360;

      const cor =
        percentual >= 90
          ? "#e74c3c"
          : percentual >= 70
          ? "#f39c12"
          : "#2ecc71";

      circle.style.background = `
        conic-gradient(
          ${cor} ${graus}deg,
          #ecf0f1 ${graus}deg
        )
      `;

      percentLabel.textContent = percentual + "%";
    }

  } catch (err) {
    console.error("Erro ao carregar uso do silo:", err);
  }
}


// ===============================
// 📐 Formata bytes em KB/MB/GB
// ===============================
function formatBytes(bytes) {
  if (!bytes || bytes <= 0) return "0 B";

  const sizes = ["B", "KB", "MB", "GB", "TB"];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));

  return (bytes / Math.pow(1024, i)).toFixed(2) + " " + sizes[i];
}


// ===============================
// 🚀 Carrega ao abrir página
// ===============================
document.addEventListener("DOMContentLoaded", carregarUsoSilo);


// ===============================
// 🔄 Permite atualizar manualmente
// ===============================
window.atualizarUsoSilo = carregarUsoSilo;