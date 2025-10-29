/**
 * RELATORIOS.JS v1.0
 * Atualiza filtros de relatórios (propriedades, áreas, cultivos, manejos)
 * Atualizado em 2025-10-29
 */

document.addEventListener("DOMContentLoaded", async () => {
  const selectProp = document.getElementById("pf-propriedades");
  const selectArea = document.getElementById("pf-area");
  const selectCult = document.getElementById("pf-cult");
  const selectMane = document.getElementById("pf-mane");

  async function carregarFiltros(propriedadesSelecionadas = []) {
    try {
      const resp = await fetch("../funcoes/relatorios/buscar_filtros_relatorio.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ propriedades: propriedadesSelecionadas })
      });
      const data = await resp.json();

      if (!data.ok) throw new Error(data.err || "Erro ao carregar filtros.");

      // === Popula propriedades ===
      if (!propriedadesSelecionadas.length) {
        selectProp.innerHTML = "";
        data.propriedades.forEach(p => {
          const opt = document.createElement("option");
          opt.value = p.id;
          opt.textContent = p.nome_razao;
          selectProp.appendChild(opt);
        });
      }

      // === Popula áreas ===
      selectArea.innerHTML = "<option value='' selected>Todas as áreas</option>";
      data.areas.forEach(a => {
        const opt = document.createElement("option");
        opt.value = a;
        opt.textContent = a;
        selectArea.appendChild(opt);
      });

      // === Popula cultivos ===
      selectCult.innerHTML = "<option value='' selected>Todos os cultivos</option>";
      data.cultivos.forEach(c => {
        const opt = document.createElement("option");
        opt.value = c;
        opt.textContent = c;
        selectCult.appendChild(opt);
      });

      // === Popula manejos ===
      selectMane.innerHTML = "<option value='' selected>Todos os tipos de manejo</option>";
      data.manejos.forEach(m => {
        const opt = document.createElement("option");
        opt.value = m;
        opt.textContent = m.charAt(0).toUpperCase() + m.slice(1);
        selectMane.appendChild(opt);
      });
    } catch (err) {
      console.error("❌ Erro ao carregar filtros:", err);
    }
  }

  // Carrega inicial
  await carregarFiltros();

  // Quando alterar propriedades, atualiza os outros filtros
  selectProp.addEventListener("change", () => {
    const propsSelecionadas = Array.from(selectProp.selectedOptions).map(o => o.value);
    carregarFiltros(propsSelecionadas);
  });
});
