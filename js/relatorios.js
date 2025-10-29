/**
 * RELATORIOS.JS v1.6
 * Seleção múltipla 100% funcional (captura cliques, Ctrl, Shift e teclado)
 */

document.addEventListener("DOMContentLoaded", async () => {
  const selectProp = document.getElementById("pf-propriedades");
  const selectArea = document.getElementById("pf-area");
  const selectCult = document.getElementById("pf-cult");
  const selectMane = document.getElementById("pf-mane");

  // === Carrega filtros ===
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

      // === Popula propriedades (primeira vez apenas) ===
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
      (data.areas || []).forEach(a => {
        const opt = document.createElement("option");
        opt.value = a;
        opt.textContent = a;
        selectArea.appendChild(opt);
      });

      if (!data.areas?.length) {
        selectArea.innerHTML += "<option>Nenhuma área encontrada</option>";
      }

      // === Cultivos ===
      selectCult.innerHTML = "<option value='' selected>Todos os cultivos</option>";
      (data.cultivos || []).forEach(c => {
        const opt = document.createElement("option");
        opt.value = c;
        opt.textContent = c;
        selectCult.appendChild(opt);
      });

      if (!data.cultivos?.length) {
        selectCult.innerHTML += "<option>Nenhum cultivo encontrado</option>";
      }

      // === Manejos ===
      selectMane.innerHTML = "<option value='' selected>Todos os tipos de manejo</option>";
      (data.manejos || []).forEach(m => {
        const opt = document.createElement("option");
        opt.value = m;
        opt.textContent = m.charAt(0).toUpperCase() + m.slice(1).replace("_", " ");
        selectMane.appendChild(opt);
      });

      if (!data.manejos?.length) {
        selectMane.innerHTML += "<option>Nenhum tipo de manejo encontrado</option>";
      }

    } catch (err) {
      console.error("❌ Erro ao carregar filtros:", err);
      alert("Erro ao carregar filtros: " + err.message);
    }
  }

  // === Inicializa com propriedades ===
  await carregarFiltros();

  // === Função de atualização dinâmica ===
  const atualizar = () => {
    const selecionadas = Array.from(selectProp.selectedOptions).map(o => o.value);
    console.log("🎯 Propriedades selecionadas:", selecionadas);

    if (selecionadas.length > 0) {
      carregarFiltros(selecionadas);
    } else {
      console.warn("⚠️ Nenhuma propriedade selecionada.");
    }
  };

  // === Eventos que funcionam em todos os cenários ===
  ["change", "click", "keyup", "mouseup", "focusout"].forEach(evt => {
    selectProp.addEventListener(evt, () => setTimeout(atualizar, 100));
  });
});
