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

    if (!txt || !bar) return;

    txt.textContent =
      `${formatBytes(usado)} de ${formatBytes(limite)} usados (${percentual}%)`;

    bar.style.width = percentual + "%";

    // 🔴 Muda cor se estiver perto do limite
    if (percentual >= 90) {
      bar.style.backgroundColor = "#e74c3c";
    } else if (percentual >= 70) {
      bar.style.backgroundColor = "#f39c12";
    } else {
      bar.style.backgroundColor = "#2ecc71";
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
document.addEventListener("DOMContentLoaded", () => {
  carregarUsoSilo();
});


// ===============================
// 🔄 Permite atualizar manualmente
// ===============================
window.atualizarUsoSilo = carregarUsoSilo;

const circle = document.querySelector(".silo-uso-circular");
const percentLabel = document.getElementById("silo-uso-percent");

const graus = (percentual / 100) * 360;

circle.style.background = `
  conic-gradient(
    ${percentual >= 90 ? "#e74c3c" : percentual >= 70 ? "#f39c12" : "#2ecc71"} ${graus}deg,
    #ecf0f1 ${graus}deg
  )
`;

percentLabel.textContent = percentual + "%";