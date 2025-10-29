/**
 * RELATORIOS.JS v1.2
 * Atualiza filtros dinamicamente (propriedades → áreas, cultivos, manejos)
 */

document.addEventListener("DOMContentLoaded", async () => {
  const selectProp = document.getElementById("pf-propriedades");
  const selectArea = document.getElementById("pf-area");
  const selectCult = document.getElementById("pf-cult");
  const selectMane = document.getElementById("pf-mane");

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
      if (!data.ok) throw new Error(data.err || "Erro ao carregar filtros.");

      // === Popula propriedades ===
      if (!propriedadesSelecionadas.length) {
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
      data.areas.forEach(a => {
        const opt = document.createElement("option");
        opt.value = a;
        opt.textContent = a;
        selectArea.appendChild(opt);
      });

      // === Cultivos ===
      selectCult.innerHTML = "<option value='' selected>Todos os cultivos</option>";
      data.cultivos.forEach(c => {
        const opt = document.createElement("option");
        opt.value = c;
        opt.textContent = c;
        selectCult.appendChild(opt);
      });

      // === ManejOS ===
      selectMane.innerHTML = "<option value='' selected>Todos os tipos de manejo</option>";
      data.manejos.forEach(m => {
        const opt = document.createElement("option");
        opt.value = m;
        opt.textContent = m.charAt(0).toUpperCase() + m.slice(1).replace("_", " ");
        selectMane.appendChild(opt);
      });

    } catch (err) {
      console.error("❌ Erro ao carregar filtros:", err);
    }
  }

  // Carrega inicial (propriedades)
  await carregarFiltros();

  // Atualiza os filtros dependentes ao alterar propriedades
  selectProp.addEventListener("change", () => {
    const selecionadas = Array.from(selectProp.selectedOptions).map(o => o.value);
    carregarFiltros(selecionadas);
  });
});
