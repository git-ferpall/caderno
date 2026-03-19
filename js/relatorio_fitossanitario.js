document.addEventListener("DOMContentLoaded", () => {

  const selectProp = document.getElementById("pf-propriedades");
  const selectArea = document.getElementById("pf-area");

  /* ===============================
  🔹 CARREGAR PROPRIEDADES
  =============================== */

  async function carregarPropriedades() {
    try {

      const resp = await fetch("/funcoes/relatorios/buscar_propriedades_areas.php");

      if (!resp.ok) {
        const txt = await resp.text();
        console.error("Erro HTTP:", txt);
        throw new Error("Erro ao buscar propriedades");
      }

      const data = await resp.json();

      if (!data.ok) throw new Error(data.err || "Erro desconhecido");

      selectProp.innerHTML = '<option value="">Selecione</option>';

      data.propriedades.forEach(p => {
        const opt = document.createElement("option");
        opt.value = p.id;
        opt.textContent = p.nome_razao;
        selectProp.appendChild(opt);
      });

    } catch (err) {
      console.error("❌ Erro propriedades:", err);
      alert("Erro ao carregar propriedades");
    }
  }

  /* ===============================
  🔹 CARREGAR ÁREAS
  =============================== */

  async function carregarAreas(propriedade_id) {

    if (!propriedade_id) {
      selectArea.innerHTML = '<option value="">Todas as áreas</option>';
      return;
    }

    try {

      const resp = await fetch(`/funcoes/relatorios/buscar_propriedades_areas.php?propriedade_id=${propriedade_id}`);

      if (!resp.ok) {
        const txt = await resp.text();
        console.error("Erro HTTP:", txt);
        throw new Error("Erro ao buscar áreas");
      }

      const data = await resp.json();

      if (!data.ok) throw new Error(data.err || "Erro ao carregar áreas");

      selectArea.innerHTML = '<option value="">Todas as áreas</option>';

      if (data.areas.length === 0) {
        selectArea.innerHTML += '<option disabled>Nenhuma área encontrada</option>';
        return;
      }

      data.areas.forEach(a => {
        const opt = document.createElement("option");
        opt.value = a.id;
        opt.textContent = a.nome;
        selectArea.appendChild(opt);
      });

    } catch (err) {
      console.error("❌ Erro áreas:", err);
      alert("Erro ao carregar áreas");
    }
  }

  /* ===============================
  🔹 EVENTOS
  =============================== */

  if (selectProp) {
    selectProp.addEventListener("change", function () {
      carregarAreas(this.value);
    });
  }

  /* ===============================
  🚀 INIT
  =============================== */

  carregarPropriedades();

});