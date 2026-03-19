/**
 * RELATORIO FITOSSANITARIO.JS
 * Baseado no relatorios.js (mesma estrutura)
 */

document.addEventListener("DOMContentLoaded", async () => {

  const selectProp = document.getElementById("pf-propriedades");
  const selectArea = document.getElementById("pf-area");

  const loading = document.getElementById("pdf-loading");
  const btn = document.getElementById("form-pdf-relatorio");
  const form = document.getElementById("rel-form");

  // ===============================
  // CARREGAR PROPRIEDADES + AREAS
  // ===============================
  async function carregarFiltros(propriedadesSelecionadas = []) {

    try {

      const params = new URLSearchParams();
      propriedadesSelecionadas.forEach(id => params.append("propriedades[]", id));

      const resp = await fetch("../funcoes/relatorios/buscar_filtros_relatorio.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString()
      });

      const data = await resp.json();

      if (!data.ok) throw new Error(data.err || "Erro ao carregar filtros");

      // PROPRIEDADES (primeira carga)
      if (!propriedadesSelecionadas.length && data.propriedades?.length) {

        selectProp.innerHTML = "";

        data.propriedades.forEach(p => {
          const opt = document.createElement("option");
          opt.value = p.id;
          opt.textContent = p.nome_razao + (p.ativo ? " (Ativa)" : "");
          selectProp.appendChild(opt);
        });

      }

      // ÁREAS
      selectArea.innerHTML = "<option value='' selected>Todas as áreas</option>";

      (data.areas || []).forEach(a => {
        const opt = document.createElement("option");
        opt.value = a.id || a; // compatível com os dois formatos
        opt.textContent = a.nome || a;
        selectArea.appendChild(opt);
      });

    } catch (err) {
      console.error("Erro filtros:", err);
      alert("Erro ao carregar filtros: " + err.message);
    }

  }

  // inicial
  await carregarFiltros();

  // ===============================
  // CHANGE SELECT2
  // ===============================
  $(document).on('change', '#pf-propriedades', function () {

    const selecionadas = $(this).val() || [];

    if (selecionadas.length > 0) {
      carregarFiltros(selecionadas);
    } else {
      selectArea.innerHTML = "<option value='' selected>Todas as áreas</option>";
    }

  });

  // ===============================
  // GERAR PDF
  // ===============================
  btn.addEventListener("click", async (e) => {

    e.preventDefault();

    if (btn.disabled) return;

    try {

      const props = $("#pf-propriedades").val();
      const data_ini = document.getElementById("pf-ini").value;
      const data_fim = document.getElementById("pf-fin").value;

      if (!props || props.length === 0) {
        alert("Selecione ao menos uma propriedade");
        return;
      }

      if (!data_ini || !data_fim) {
        alert("Informe o período");
        return;
      }

      btn.disabled = true;
      btn.style.opacity = "0.6";

      if (loading) loading.style.display = "flex";

      const formData = new FormData(form);

      const resp = await fetch("../relatorios/pdf_fitossanitario.php", {
        method: "POST",
        body: formData
      });

      if (!resp.ok) throw new Error("Erro ao gerar PDF");

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

    }

  });

});