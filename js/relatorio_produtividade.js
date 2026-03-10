/**
 * RELATORIO PRODUTIVIDADE
 * Carrega propriedades, áreas e produtos dinamicamente
 */

document.addEventListener("DOMContentLoaded", async () => {

  const selectProp = document.getElementById("pf-propriedade");
  const selectArea = document.getElementById("pf-area");
  const selectProd = document.getElementById("pf-produto");

  async function carregarFiltros(propriedade = "") {

    try {

      const params = new URLSearchParams();

      if (propriedade)
        params.append("propriedade", propriedade);

      const resp = await fetch("../funcoes/relatorios/buscar_filtros_produtividade.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: params.toString()
      });

      const data = await resp.json();

      if (!data.ok)
        throw new Error(data.err || "Erro ao carregar filtros");

      /* PROPRIEDADES */

      if (selectProp && !propriedade) {

        selectProp.innerHTML = "<option value=''>Selecione</option>";

        data.propriedades.forEach(p => {

          const opt = document.createElement("option");

          opt.value = p.id;
          opt.textContent = p.nome_razao;

          selectProp.appendChild(opt);

        });

      }

      /* AREAS */

      if (selectArea) {

        selectArea.innerHTML = "<option value=''>Todas as áreas</option>";

        data.areas.forEach(a => {

          const opt = document.createElement("option");

          opt.value = a.id;
          opt.textContent = a.nome;

          selectArea.appendChild(opt);

        });

      }

      /* PRODUTOS */

      if (selectProd) {

        selectProd.innerHTML = "<option value=''>Todos os produtos</option>";

        data.produtos.forEach(p => {

          const opt = document.createElement("option");

          opt.value = p.id;
          opt.textContent = p.nome;

          selectProd.appendChild(opt);

        });

      }

    } catch (err) {

      console.error("Erro ao carregar filtros:", err);

    }

  }

  /* carregamento inicial */

  await carregarFiltros();

  /* quando muda propriedade */

  if (selectProp) {

    selectProp.addEventListener("change", () => {

      carregarFiltros(selectProp.value);

    });

  }

});