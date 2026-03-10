/**
 * RELATORIO PRODUTIVIDADE
 * Funciona igual ao relatorios.js
 */

document.addEventListener("DOMContentLoaded", async () => {

  const selectProp = document.getElementById("pf-propriedade");
  const selectArea = document.getElementById("pf-area");
  const selectProd = document.getElementById("pf-produto");

  async function carregarFiltros(propriedadesSelecionadas = []) {

    try {

      const params = new URLSearchParams();

      propriedadesSelecionadas.forEach(id => {
        params.append("propriedades[]", id);
      });

      console.log("🧾 propriedades enviadas:", propriedadesSelecionadas);

      const resp = await fetch("../funcoes/relatorios/buscar_filtros_produtividade.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString()
      });

      const data = await resp.json();

      console.log("📦 retorno:", data);

      if (!data.ok) throw new Error(data.err || "Erro ao carregar filtros");

      /* =========================
         PROPRIEDADES
      ========================= */

      if (!propriedadesSelecionadas.length && data.propriedades) {

        selectProp.innerHTML = "<option value=''>Selecione</option>";

        data.propriedades.forEach(p => {

          const opt = document.createElement("option");
          opt.value = p.id;
          opt.textContent = p.nome_razao;

          selectProp.appendChild(opt);

        });

      }

      /* =========================
         AREAS
      ========================= */

      selectArea.innerHTML = "<option value=''>Todas as áreas</option>";

      (data.areas || []).forEach(a => {

        const opt = document.createElement("option");

        if (typeof a === "object") {
          opt.value = a.id;
          opt.textContent = a.nome;
        } else {
          opt.value = a;
          opt.textContent = a;
        }

        selectArea.appendChild(opt);

      });

      /* =========================
         PRODUTOS
      ========================= */

      selectProd.innerHTML = "<option value=''>Todos os produtos</option>";

      (data.produtos || []).forEach(p => {

        const opt = document.createElement("option");

        if (typeof p === "object") {
          opt.value = p.id;
          opt.textContent = p.nome;
        } else {
          opt.value = p;
          opt.textContent = p;
        }

        selectProd.appendChild(opt);

      });

    } catch (err) {

      console.error("❌ erro:", err);

    }

  }

  /* =========================
     CARREGAMENTO INICIAL
  ========================= */

  await carregarFiltros();

  /* =========================
     MUDANÇA DE PROPRIEDADE
  ========================= */

  if (selectProp) {

    selectProp.addEventListener("change", function () {

      const selecionada = this.value;

      console.log("🎯 propriedade selecionada:", selecionada);

      if (selecionada) {
        carregarFiltros([selecionada]);
      } else {
        selectArea.innerHTML = "<option value=''>Todas as áreas</option>";
        selectProd.innerHTML = "<option value=''>Todos os produtos</option>";
      }

    });

  }

});