document.addEventListener("DOMContentLoaded", () => {

  const selectProp = document.getElementById("pf-propriedade");
  const selectArea = document.getElementById("pf-area");
  const selectProd = document.getElementById("pf-produto");

  /* =====================================
     CARREGA PROPRIEDADES
  ===================================== */

  async function carregarPropriedades() {

    try {

      const resp = await fetch("../funcoes/relatorios/buscar_filtros_produtividade.php", {
        method: "POST"
      });

      const data = await resp.json();

      if (!data.ok) throw new Error(data.err);

      selectProp.innerHTML = "<option value=''>Selecione</option>";

      data.propriedades.forEach(p => {

        const opt = document.createElement("option");
        opt.value = p.id;
        opt.textContent = p.nome_razao;

        selectProp.appendChild(opt);

      });

    } catch (err) {

      console.error("Erro propriedades:", err);

    }

  }

  /* =====================================
     CARREGA FILTROS DA PROPRIEDADE
  ===================================== */

  async function carregarFiltros(propriedade) {

    try {

      const params = new URLSearchParams();
      params.append("propriedades[]", propriedade);

      const resp = await fetch("../funcoes/relatorios/buscar_filtros_produtividade.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: params.toString()
      });

      const data = await resp.json();

      if (!data.ok) throw new Error(data.err);

      /* limpa selects */

      selectArea.innerHTML = "<option value=''>Todas as áreas</option>";
      selectProd.innerHTML = "<option value=''>Todos os produtos</option>";

      /* AREAS */

      (data.areas || []).forEach(a => {

        const opt = document.createElement("option");
        opt.value = a.id;
        opt.textContent = a.nome;

        selectArea.appendChild(opt);

      });

      /* PRODUTOS */

      (data.produtos || []).forEach(p => {

        const opt = document.createElement("option");
        opt.value = p.id;
        opt.textContent = p.nome;

        selectProd.appendChild(opt);

      });

    } catch (err) {

      console.error("Erro filtros:", err);

    }

  }

  /* =====================================
     EVENTO PROPRIEDADE
  ===================================== */

  selectProp.addEventListener("change", function () {

    const propriedade = this.value;

    /* limpa sempre */

    selectArea.innerHTML = "<option value=''>Todas as áreas</option>";
    selectProd.innerHTML = "<option value=''>Todos os produtos</option>";

    if (!propriedade) return;

    carregarFiltros(propriedade);

  });

  /* =====================================
     INICIO
  ===================================== */

  carregarPropriedades();

});