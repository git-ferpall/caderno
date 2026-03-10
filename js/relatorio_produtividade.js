/**
 * RELATORIO PRODUTIVIDADE
 * Carrega propriedades, áreas e produtos dinamicamente
 */

document.addEventListener("DOMContentLoaded", async () => {

  const selectProp = document.getElementById("pf-propriedade");
  const selectArea = document.getElementById("pf-area");
  const selectProd = document.getElementById("pf-produto");

  /* =========================================
     FUNÇÃO PRINCIPAL
  ========================================= */

  async function carregarFiltros(propriedadesSelecionadas = []) {

    try {

      const params = new URLSearchParams();

      propriedadesSelecionadas.forEach(id => {
        params.append("propriedades[]", id);
      });

      console.log("🧾 Enviando propriedades:", propriedadesSelecionadas);

      const resp = await fetch("../funcoes/relatorios/buscar_filtros_produtividade.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: params.toString()
      });

      if (!resp.ok) {
        throw new Error("Erro na requisição");
      }

      const data = await resp.json();

      console.log("📦 Retorno:", data);

      if (!data.ok) {
        throw new Error(data.err || "Erro ao carregar filtros");
      }

      /* =========================================
         PROPRIEDADES
      ========================================= */

      if (!propriedadesSelecionadas.length && data.propriedades) {

        selectProp.innerHTML = "<option value=''>Selecione</option>";

        data.propriedades.forEach(p => {

          const opt = document.createElement("option");

          opt.value = p.id;
          opt.textContent = p.nome_razao;

          selectProp.appendChild(opt);

        });

      }

      /* =========================================
         AREAS
      ========================================= */

      selectArea.innerHTML = "<option value=''>Todas as áreas</option>";

      (data.areas || []).forEach(a => {

        const opt = document.createElement("option");

        opt.value = a.id;
        opt.textContent = a.nome;

        selectArea.appendChild(opt);

      });

      if (!data.areas || !data.areas.length) {

        const opt = document.createElement("option");

        opt.disabled = true;
        opt.textContent = "Nenhuma área encontrada";

        selectArea.appendChild(opt);

      }

      /* =========================================
         PRODUTOS
      ========================================= */

      selectProd.innerHTML = "<option value=''>Todos os produtos</option>";

      (data.produtos || []).forEach(p => {

        const opt = document.createElement("option");

        opt.value = p.id;
        opt.textContent = p.nome;

        selectProd.appendChild(opt);

      });

    } catch (err) {

      console.error("❌ Erro ao carregar filtros:", err);

    }

  }

  /* =========================================
     CARREGAMENTO INICIAL
  ========================================= */

  await carregarFiltros();

  /* =========================================
     FILTRO POR PROPRIEDADE
  ========================================= */

  if (selectProp) {

    selectProp.addEventListener("change", function () {

      const propriedade = this.value;

      console.log("🎯 Propriedade selecionada:", propriedade);

      if (propriedade) {

        carregarFiltros([propriedade]);

      } else {

        selectArea.innerHTML = "<option value=''>Todas as áreas</option>";
        selectProd.innerHTML = "<option value=''>Todos os produtos</option>";

      }

    });

  }

});


/* =====================================================
   GERAR RELATORIO PDF
===================================================== */

document.addEventListener("DOMContentLoaded", () => {

  const btn = document.getElementById("form-pdf-relatorio");
  const form = document.getElementById("rel-form");
  const loading = document.getElementById("pdf-loading");

  if (!btn || !form) return;

  btn.addEventListener("click", async (e) => {

    e.preventDefault();

    if (btn.disabled) return;

    try {

      btn.disabled = true;
      btn.style.opacity = "0.6";
      btn.style.cursor = "not-allowed";

      if (loading) loading.style.display = "flex";

      const formData = new FormData(form);

      const resp = await fetch("../funcoes/relatorios/relatorio_safra_pdf.php", {
        method: "POST",
        body: formData
      });

      if (!resp.ok) {
        throw new Error("Erro ao gerar PDF");
      }

      const blob = await resp.blob();
      const url = URL.createObjectURL(blob);

      window.open(url, "_blank");

    } catch (err) {

      console.error(err);
      alert("❌ Falha ao gerar relatório: " + err.message);

    } finally {

      if (loading) loading.style.display = "none";

      btn.disabled = false;
      btn.style.opacity = "1";
      btn.style.cursor = "pointer";

    }

  });

});