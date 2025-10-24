// js/estufa.js
export async function carregarEstufas() {
  const lista = document.getElementById("lista-estufas");
  if (!lista) return;

  lista.innerHTML = "<div class='item-none'>Carregando estufas...</div>";

  try {
    const resp = await fetch("../funcoes/get_estufas.php");
    const data = await resp.json();

    if (!data.ok || !data.estufas || data.estufas.length === 0) {
      lista.innerHTML = "<div class='item-none'>Nenhuma estufa cadastrada.</div>";
      return;
    }

    lista.innerHTML = "";

    data.estufas.forEach(estufa => {
      const area = estufa.area || "Não informada";
      const obs = estufa.obs || "Nenhuma observação";

      const div = document.createElement("div");
      div.className = "item item-propriedade item-estufa v2";
      div.id = `estufa-${estufa.id}`;
      div.innerHTML = `
        <h4 class="item-title">${estufa.nome}</h4>
        <div class="item-edit">
          <button class="edit-btn" type="button" onclick="selectEstufa(${estufa.id})">Selecionar</button>
        </div>
      `;

      const detalhes = document.createElement("div");
      detalhes.className = "item-estufa-box d-none";
      detalhes.id = `estufa-${estufa.id}-box`;
      detalhes.innerHTML = `
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
              estufa.bancadas && estufa.bancadas.length
                ? estufa.bancadas
                    .map(
                      b => `<button type="button" class="item-bancada">${b.nome}</button>`
                    )
                    .join("")
                : `<div class="item-none">Nenhuma bancada cadastrada.</div>`
            }
          </div>
        </div>
      `;

      lista.appendChild(div);
      lista.appendChild(detalhes);
    });
  } catch (e) {
    console.error("Erro ao carregar estufas:", e);
    lista.innerHTML = "<div class='item-none'>Erro ao carregar estufas.</div>";
  }
}
