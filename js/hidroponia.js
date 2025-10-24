document.addEventListener("DOMContentLoaded", () => {

  /* =======================================================
     🔹 CARREGAR ESTUFAS DO BANCO
  ======================================================= */
  carregarEstufas();

  async function carregarEstufas() {
    const box = document.getElementById("lista-estufas");
    if (!box) return;

    box.innerHTML = '<div class="item-none">Carregando estufas...</div>';

    try {
      const r = await fetch("../funcoes/get_estufas.php");
      const j = await r.json();

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
                  <button class="edit-btn" type="button" onclick="selectEstufa(${e.id})">
                      Selecionar
                  </button>
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
                          <button class="main-btn btn-alter btn-alter-item fundo-verde" id="bancada-add-estufa-${e.id}" type="button">
                              <div class="btn-icon icon-plus cor-verde"></div>
                              <span class="main-btn-text">Nova Bancada</span>
                          </button>
                      </div>

                      <div class="item-add-box" id="item-add-bancada-estufa-${e.id}">
                          <div class="item-add-box-p">
                              <div class="form-campo">
                                  <label class="item-label" for="b-nome-${e.id}">Número/Nome da Bancada</label>
                                  <input type="text" class="form-text" id="b-nome-${e.id}" placeholder="Ex: 01, 02..." required>
                              </div>

                              <div class="form-campo">
                                  <label class="item-label" for="b-area-${e.id}">Cultura/Espécie</label>
                                  <input type="text" class="form-text" id="b-area-${e.id}" placeholder="Cultura ou espécie (Opcional)">
                              </div>

                              <div class="form-campo">
                                  <label for="b-obs-${e.id}">Observações</label>
                                  <textarea class="form-text form-textarea" id="b-obs-${e.id}" placeholder="Insira aqui suas observações..."></textarea>
                              </div>

                              <div class="form-submit">
                                  <button class="item-btn fundo-cinza-b cor-preto form-cancel" type="button" onclick="voltarEstufa(${e.id})">
                                      <span class="main-btn-text">Cancelar</span>
                                  </button>
                                  <button class="item-btn fundo-verde form-save" type="button" onclick="salvarBancada(${e.id})">
                                      <span class="main-btn-text">Salvar</span>
                                  </button>
                              </div>
                          </div>
                      </div>
                  </form>
              </div>
          </div>
        `;
      });

      box.innerHTML = html;

    } catch (err) {
      console.error(err);
      box.innerHTML = '<div class="item-none">Erro ao carregar estufas.</div>';
    }
  }

  /* =======================================================
     🔹 SALVAR NOVA ESTUFA
  ======================================================= */
  const btnAddEstufa = document.getElementById("form-save-estufa");
  if (btnAddEstufa) {
    btnAddEstufa.addEventListener("click", async () => {
      const nome = document.getElementById("e-nome").value.trim();
      const area = document.getElementById("e-area").value.trim();
      const obs = document.getElementById("e-obs").value.trim();

      if (!nome) {
        mostrarMensagem("Informe o nome da estufa.", "erro");
        return;
      }

      const data = new FormData();
      data.append("nome", nome);
      data.append("area_m2", area);
      data.append("observacoes", obs);

      btnAddEstufa.disabled = true;
      btnAddEstufa.innerHTML = "Salvando...";

      try {
        const r = await fetch("../funcoes/add_estufa.php", { method: "POST", body: data });
        const j = await r.json();

        if (j.ok) {
          mostrarMensagem("Estufa cadastrada com sucesso!", "sucesso");
          setTimeout(() => location.reload(), 1000);
        } else {
          mostrarMensagem("Erro: " + (j.err || "Falha ao cadastrar."), "erro");
        }
      } catch (e) {
        console.error(e);
        mostrarMensagem("Falha ao conectar com o servidor.", "erro");
      }

      btnAddEstufa.disabled = false;
      btnAddEstufa.innerHTML = '<span class="main-btn-text">Salvar</span>';
    });
  }

  /* =======================================================
     🔹 SALVAR NOVA BANCADA (área)
  ======================================================= */
  window.salvarBancada = async function (estufaId) {
    const nome = document.getElementById(`b-nome-${estufaId}`).value.trim();
    const cultura = document.getElementById(`b-area-${estufaId}`).value.trim();
    const obs = document.getElementById(`b-obs-${estufaId}`).value.trim();

    if (!nome) {
      mostrarMensagem("Informe o nome da bancada.", "erro");
      return;
    }

    try {
      // 1️⃣ cadastra a área (bancada)
      const areaData = new FormData();
      areaData.append("nome", nome);
      areaData.append("tipo", "estufa");
      areaData.append("observacoes", obs);
      areaData.append("cultura", cultura);

      const areaResp = await fetch("../funcoes/add_area.php", { method: "POST", body: areaData });
      const areaJson = await areaResp.json();

      if (!areaJson.ok) {
        mostrarMensagem("Erro ao cadastrar área: " + (areaJson.err || ""), "erro");
        return;
      }

      // 2️⃣ vincula a área à estufa
      const vinculo = new FormData();
      vinculo.append("estufa_id", estufaId);
      vinculo.append("area_id", areaJson.id);

      const vincResp = await fetch("../funcoes/vincular_area_estufa.php", { method: "POST", body: vinculo });
      const vincJson = await vincResp.json();

      if (vincJson.ok) {
        mostrarMensagem("Bancada vinculada à estufa com sucesso!", "sucesso");
        setTimeout(() => location.reload(), 1000);
      } else {
        mostrarMensagem("Erro ao vincular bancada: " + (vincJson.err || ""), "erro");
      }
    } catch (e) {
      console.error(e);
      mostrarMensagem("Erro inesperado ao salvar bancada.", "erro");
    }
  };

  /* =======================================================
     🔹 FUNÇÕES DE UI
  ======================================================= */
  window.selectEstufa = function (id) {
    document.querySelectorAll(".item-estufa-box").forEach(el => el.classList.add("d-none"));
    const box = document.getElementById(`estufa-${id}-box`);
    if (box) box.classList.remove("d-none");
  };

  window.voltarEstufa = function (id) {
    document.querySelectorAll(".item-bancada-content").forEach(el => el.classList.add("d-none"));
    const box = document.getElementById(`estufa-${id}-box`);
    if (box) box.classList.remove("d-none");
  };

  window.selectBancada = function (bancadaNome, estufaId) {
    document.querySelectorAll(".item-bancada-content").forEach(el => el.classList.add("d-none"));
    const el = document.getElementById(`item-bancada-${bancadaNome}-content-estufa-${estufaId}`);
    if (el) el.classList.remove("d-none");
  };

  /* =======================================================
     🔹 FUNÇÃO DE MENSAGEM BONITA (toast simples)
  ======================================================= */
  function mostrarMensagem(msg, tipo = "info") {
    let box = document.getElementById("msg-toast");
    if (!box) {
      box = document.createElement("div");
      box.id = "msg-toast";
      box.style.position = "fixed";
      box.style.bottom = "20px";
      box.style.right = "20px";
      box.style.padding = "12px 16px";
      box.style.borderRadius = "8px";
      box.style.color = "#fff";
      box.style.fontSize = "14px";
      box.style.zIndex = "9999";
      box.style.boxShadow = "0 2px 8px rgba(0,0,0,0.2)";
      document.body.appendChild(box);
    }

    box.style.background = tipo === "erro" ? "#d9534f" :
                           tipo === "sucesso" ? "#5cb85c" : "#0275d8";
    box.textContent = msg;
    box.style.display = "block";

    setTimeout(() => {
      box.style.display = "none";
    }, 3000);
  }

});
