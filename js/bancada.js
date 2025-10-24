// js/bancada.js
export function inicializarBancadas() {
  console.log("✅ Módulo de bancadas inicializado");

  // Exemplo simples: clicar na estufa e abrir/fechar detalhes
  window.selectEstufa = function (id) {
    const box = document.getElementById(`estufa-${id}-box`);
    if (!box) return;

    const todas = document.querySelectorAll(".item-estufa-box");
    todas.forEach(el => {
      if (el !== box) el.classList.add("d-none");
    });

    box.classList.toggle("d-none");
  };
}
