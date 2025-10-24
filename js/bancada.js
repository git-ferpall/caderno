// js/hidroponia/bancada.js
import { mostrarMensagem } from './utils.js';

export function inicializarBancadas() {
  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[id^='bancada-add-estufa-']");
    if (!btn) return;

    const estufaId = btn.id.split("-").pop();
    const box = document.getElementById(`item-add-bancada-estufa-${estufaId}`);
    if (box) box.classList.toggle("d-none");
  });
}

window.salvarBancada = async function (estufaId) {
  const nome = document.getElementById(`b-nome-${estufaId}`)?.value.trim();
  const cultura = document.getElementById(`b-area-${estufaId}`)?.value.trim();
  const obs = document.getElementById(`b-obs-${estufaId}`)?.value.trim();

  if (!nome) {
    mostrarMensagem("Informe o nome da bancada.", "erro");
    return;
  }

  try {
    const data = new FormData();
    data.append("nome", nome);
    data.append("tipo", "estufa");
    data.append("observacoes", obs);
    data.append("cultura", cultura);

    const r1 = await fetch("../funcoes/add_area.php", { method: "POST", body: data });
    const j1 = await r1.json();
    if (!j1.ok) throw new Error(j1.err || "Erro ao criar área");

    const vinc = new FormData();
    vinc.append("estufa_id", estufaId);
    vinc.append("area_id", j1.id);

    const r2 = await fetch("../funcoes/vincular_area_estufa.php", { method: "POST", body: vinc });
    const j2 = await r2.json();
    if (!j2.ok) throw new Error(j2.err || "Erro ao vincular bancada");

    mostrarMensagem("Bancada cadastrada com sucesso!", "sucesso");
    setTimeout(() => location.reload(), 1000);

  } catch (e) {
    console.error(e);
    mostrarMensagem(e.message, "erro");
  }
};

window.voltarEstufa = function (id) {
  document.querySelectorAll(".item-bancada-content").forEach(el => el.classList.add("d-none"));
  const addBox = document.getElementById(`item-add-bancada-estufa-${id}`);
  if (addBox) addBox.classList.add("d-none");
  const box = document.getElementById(`estufa-${id}-box`);
  if (box) box.classList.remove("d-none");
};
