/**
 * RELATORIOS.JS v1.3
 * Atualiza filtros dinamicamente (propriedades → áreas, cultivos, manejos)
 * Com logs detalhados para debug
 */

document.addEventListener("DOMContentLoaded", async () => {
  const selectProp = document.getElementById("pf-propriedades");
  const selectArea = document.getElementById("pf-area");
  const selectCult = document.getElementById("pf-cult");
  const selectMane = document.getElementById("pf-mane");

  // === Função principal ===
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
      if (data.areas?.length) {
        data.areas.forEach(a => {
          const opt = document.createElement("option");
          opt.value = a;
          opt.textContent = a;
          selectArea.appendChild(opt);
        });
      } else {
        const opt = document.createElement("option");
        opt.textContent = "Nenhuma área encontrada";
        selectArea.appendChild(opt);
      }

      // === Cultivos ===
      selectCult.innerHTML = "<option value='' selected>Todos os cultivos</option>";
      if (data.cultivos?.length) {
        data.cultivos.forEach(c => {
          const opt = document.createElement("option");
          opt.value = c;
          opt.textContent = c;
          selectCult.appendChild(opt);
        });
      } else {
        const opt = document.createElement("option");
        opt.textContent = "Nenhum cultivo encontrado";
        selectCult.appendChild(opt);
      }

      // === ManejOS ===
      selectMane.innerHTML = "<option value='' selected>Todos os tipos de manejo</option>";
      if (data.manejos?.length) {
        data.manejos.forEach(m => {
          const opt = document.createElement("option");
          opt.value = m;
          opt.textContent = m.charAt(0).toUpperCase() + m.slice(1).replace("_", " ");
          selectMane.appendChild(opt);
        });
      } else {
        const opt = document.createElement("option");
        opt.textContent = "Nenhum tipo de manejo encontrado";
        selectMane.appendChild(opt);
      }

    } catch (err) {
      console.error("❌ Erro ao carregar filtros:", err);
      alert("Erro ao carregar filtros: " + err.message);
    }
  }

  // === Inicializa propriedades ===
  await carregarFiltros();

  // === Atualiza dinamicamente quando muda seleção ===
  selectProp.addEventListener("change", () => {
    const selecionadas = Array.from(selectProp.selectedOptions).map(o => o.value);
    carregarFiltros(selecionadas);
  });
});
