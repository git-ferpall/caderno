// js/hidroponia/estufa.js
import { mostrarMensagem } from './utils.js';

export function carregarEstufas() {
  const box = document.getElementById("lista-estufas");
  if (!box) return;

  box.innerHTML = '<div class="item-none">Carregando estufas...</div>';

  fetch("../funcoes/get_estufas.php")
    .then(r => r.json())
    .then(j => {
      if (!j.ok || !j.estufas || j.estufas.length === 0) {
        box.innerHTML = '<div class="item-none">Nenhuma estufa cadastrada.</div>';
        return;
      }

      let html = "";
      j.estufas.forEach(e => {
        const area = e.area ? e.area + " m²" : "Não informada";
        const obs = e.obs ? e.obs : "Nenhuma observação";

        html += `
          <div class="item item-propriedade item-estufa v2" id="estufa-${e.id}">
              <h4 class="item-title">${e.nome}</h4>
              <div class="item-edit">
                  <button class="edit-btn" type="button" onclick="selectEstufa(${e.id})">Selecionar</button>
              </div>
          </div>

          <div class="item-estufa-box d-none" id="estufa-${e.id}-box">
              <div class="item-estufa-header">
                  <div class="item-estufa-header-box">
                      <div class="item-estufa-title">Área (m²)</div>
                      <div class="item-estufa-text">${area}</div>
                  </div>
                  <div class="item-estufa-header-box">
                      <div class="item-estufa-title">Observações</div>
                      <div class="item-estufa-text">${obs}</div>
                  </div>
              </div>

              <div class="item-bancadas">
                  <h4 class="item-bancadas-title">Bancadas</h4>
                  <div class="item-bancadas-box">
                      ${
                        e.bancadas && e.bancadas.length > 0
                          ? e.bancadas.map(b => `
                              <button type="button" class="item-bancada"
                                  onclick="selectBancada('${b.nome}', ${e.id})">
                                  <div class="item-bancada-title">${b.nome}</div>
                              </button>
                            `).join("")
                          : '<div class="item-none">Nenhuma bancada cadastrada.</div>'
                      }
                  </div>

                  <form class="main-form form-bancada" id="add-bancada-estufa-${e.id}">
                      <div class="item-add">
                          <button class="main-btn btn-alter btn-alter-item fundo-verde"
                              id="bancada-add-estufa-${e.id}" type="button">
                              <div class="btn-icon icon-plus cor-verde"></div>
                              <span class="main-btn-text">Nova Bancada</span>
                          </button>
                      </div>
                      <div class="item-add-box d-none" id="item-add-bancada-estufa-${e.id}"></div>
                  </form>
              </div>
          </div>`;
      });

      box.innerHTML = html;
    })
    .catch(() => {
      box.innerHTML = '<div class="item-none">Erro ao carregar estufas.</div>';
    });
}

// Abre/fecha estufa (toggle)
window.selectEstufa = function (id) {
  const box = document.getElementById(`estufa-${id}-box`);
  if (!box) return;

  const aberta = !box.classList.contains("d-none");
  document.querySelectorAll(".item-estufa-box").forEach(el => el.classList.add("d-none"));
  if (!aberta) box.classList.remove("d-none");
};
