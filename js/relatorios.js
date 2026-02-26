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
document.getElementById("form-pdf-relatorio").addEventListener("click", () => {
  const form = document.getElementById("rel-form");
  const formData = new FormData(form);

  fetch("../funcoes/relatorios/gerar_relatorio_pdf.php", {
    method: "POST",
    body: formData
  })
    .then(resp => {
      if (!resp.ok) throw new Error("Erro ao gerar PDF");
      return resp.blob();
    })
    .then(blob => {
      const url = URL.createObjectURL(blob);
      window.open(url, "_blank");
    })
    .catch(err => {
      alert("❌ Falha ao gerar PDF: " + err.message);
      console.error(err);
    });
});
document.getElementById('form-pdf-relatorio')
.addEventListener('click', function() {

    const form = document.getElementById('rel-form');

    // 🔹 Mostra loading
    document.getElementById('loading-overlay').style.display = 'flex';

    // 🔹 Cria nova aba
    const newTab = window.open('', '_blank');

    // 🔹 Envia form para nova aba
    form.target = newTab.name;

    form.submit();

    // 🔹 Remove loading depois de pequeno tempo (ajuste se quiser)
    setTimeout(() => {
        document.getElementById('loading-overlay').style.display = 'none';
    }, 2000);
});