/**
 * RELATORIO_SAFRA.JS
 * Carrega filtros dinâmicos: propriedade → área → produto
 */

document.addEventListener("DOMContentLoaded", async () => {

  const selectProp = document.getElementById("pf-propriedade");
  const selectArea = document.getElementById("pf-area");
  const selectProd = document.getElementById("pf-produto");

  async function carregarFiltros(propriedade = "") {

    try {

      const params = new URLSearchParams();
      if (propriedade) params.append("propriedade", propriedade);

      const resp = await fetch("../funcoes/relatorios/buscar_filtros_safra.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: params.toString()
      });

      const data = await resp.json();

      if (!data.ok) throw new Error(data.err);

      /* === PROPRIEDADES === */

      if (!propriedade) {

        selectProp.innerHTML = "<option value=''>Selecione</option>";

        data.propriedades.forEach(p => {

          const opt = document.createElement("option");
          opt.value = p.id;
          opt.textContent = p.nome_razao;

          selectProp.appendChild(opt);

        });

      }

      /* === AREAS === */

      selectArea.innerHTML = "<option value=''>Todas as áreas</option>";

      data.areas.forEach(a => {

        const opt = document.createElement("option");
        opt.value = a.id;
        opt.textContent = a.nome;

        selectArea.appendChild(opt);

      });

      /* === PRODUTOS === */

      selectProd.innerHTML = "<option value=''>Todos os produtos</option>";

      data.produtos.forEach(p => {

        const opt = document.createElement("option");
        opt.value = p.id;
        opt.textContent = p.nome;

        selectProd.appendChild(opt);

      });

    } catch (err) {

      console.error("Erro ao carregar filtros:", err);
      alert("Erro ao carregar filtros.");

    }

  }

  /* === carregamento inicial === */

  await carregarFiltros();

  /* === mudança de propriedade === */

  selectProp.addEventListener("change", () => {

    const prop = selectProp.value;

    if (prop) {
      carregarFiltros(prop);
    }

  });

});