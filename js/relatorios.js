/**
 * RELATORIOS.JS v2.0
 * Totalmente compatível com Select2 (multi-select) e carregamento dinâmico de filtros
 */

document.addEventListener("DOMContentLoaded", async () => {
  const selectProp = document.getElementById("pf-propriedades");
  const selectArea = document.getElementById("pf-area");
  const selectCult = document.getElementById("pf-cult");
  const selectMane = document.getElementById("pf-mane");

  // === Função principal de carregamento de filtros ===
  async function carregarFiltros(propriedadesSelecionadas = []) {
    try {
      const params = new URLSearchParams();
      propriedadesSelecionadas.forEach(id => params.append("propriedades[]", id));

      console.log("🧾 Enviando propriedades:", propriedadesSelecionadas);

      const resp = await fetch("../funcoes/relatorios/buscar_filtros_relatorio.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString()
      });

      const data = await resp.json();
      console.log("📦 Retorno do servidor:", data);

      if (!data.ok) throw new Error(data.err || "Erro ao carregar filtros.");

      // === Preenche lista de propriedades (somente na primeira execução) ===
      if (!propriedadesSelecionadas.length && data.propriedades?.length) {
        selectProp.innerHTML = "";
        data.propriedades.forEach(p => {
          const opt = document.createElement("option");
          opt.value = p.id;
          opt.textContent = p.nome_razao + (p.ativo ? " (Ativa)" : "");
          selectProp.appendChild(opt);
        });
      }

      // === Áreas ===
      selectArea.innerHTML = "<option value='' selected>Todas as áreas</option>";
      (data.areas || []).forEach(a => {
        const opt = document.createElement("option");
        opt.value = a;
        opt.textContent = a;
        selectArea.appendChild(opt);
      });
      if (!data.areas?.length)
        selectArea.innerHTML += "<option disabled>Nenhuma área encontrada</option>";

      // === Cultivos ===
      selectCult.innerHTML = "<option value='' selected>Todos os cultivos</option>";
      (data.cultivos || []).forEach(c => {
        const opt = document.createElement("option");
        opt.value = c;
        opt.textContent = c;
        selectCult.appendChild(opt);
      });
      if (!data.cultivos?.length)
        selectCult.innerHTML += "<option disabled>Nenhum cultivo encontrado</option>";

      // === Manejos ===
      selectMane.innerHTML = "<option value='' selected>Todos os tipos de manejo</option>";
      (data.manejos || []).forEach(m => {
        const opt = document.createElement("option");
        opt.value = m;
        opt.textContent = m.charAt(0).toUpperCase() + m.slice(1).replace("_", " ");
        selectMane.appendChild(opt);
      });
      if (!data.manejos?.length)
        selectMane.innerHTML += "<option disabled>Nenhum manejo encontrado</option>";

    } catch (err) {
      console.error("❌ Erro ao carregar filtros:", err);
      alert("Erro ao carregar filtros: " + err.message);
    }
  }

  // === Carregamento inicial ===
  await carregarFiltros();

  // === Detectar seleção via Select2 (eventos jQuery) ===
  $(document).on('change', '#pf-propriedades', function (e) {
    const selecionadas = $(this).val() || [];
    console.log("🎯 Propriedades selecionadas (Select2):", selecionadas);

    if (selecionadas.length > 0) {
      carregarFiltros(selecionadas);
    } else {
      // limpa filtros se nada selecionado
      selectArea.innerHTML = "<option value='' selected>Todas as áreas</option>";
      selectCult.innerHTML = "<option value='' selected>Todos os cultivos</option>";
      selectMane.innerHTML = "<option value='' selected>Todos os tipos de manejo</option>";
    }
  });
});
document.getElementById("form-pdf-relatorio").addEventListener("click", async (e) => {
  e.preventDefault();

  const btn = document.getElementById("form-pdf-relatorio");
  const form = document.getElementById("rel-form");
  const loading = document.getElementById("pdf-loading");

  if (btn.disabled) return; // evita duplo clique

  try {
    btn.disabled = true;
    btn.style.opacity = "0.6";
    btn.style.cursor = "not-allowed";

    loading.style.display = "flex";

    const formData = new FormData(form);

    const resp = await fetch("../funcoes/relatorios/gerar_relatorio_pdf.php", {
      method: "POST",
      body: formData
    });

    if (!resp.ok) throw new Error("Erro ao gerar PDF");

    const blob = await resp.blob();
    const url = URL.createObjectURL(blob);
    window.open(url, "_blank");

  } catch (err) {
    alert("❌ Falha ao gerar PDF: " + err.message);
    console.error(err);
  } finally {
    loading.style.display = "none";
    btn.disabled = false;
    btn.style.opacity = "1";
    btn.style.cursor = "pointer";
  }
});
