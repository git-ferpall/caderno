/**
 * RELATORIOS.JS v1.8
 * Compatível com <select multiple>, detecta seleção por clique, Ctrl, Shift e teclado
 * Atualiza filtros (áreas, cultivos e manejos) dinamicamente
 */

document.addEventListener("DOMContentLoaded", async () => {
  const selectProp = document.getElementById("pf-propriedades");
  const selectArea = document.getElementById("pf-area");
  const selectCult = document.getElementById("pf-cult");
  const selectMane = document.getElementById("pf-mane");

  // === Função de carregamento de filtros ===
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

      // === Popula propriedades (somente na primeira execução) ===
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

  // === Carga inicial ===
  await carregarFiltros();

  // === Detecta mudanças no select de propriedades ===
  function atualizar() {
    const selecionadas = Array.from(selectProp.selectedOptions).map(o => o.value);
    console.log("🎯 Propriedades selecionadas:", selecionadas);
    if (selecionadas.length > 0) {
      carregarFiltros(selecionadas);
    } else {
      // limpa filtros se nada selecionado
      selectArea.innerHTML = "<option value='' selected>Todas as áreas</option>";
      selectCult.innerHTML = "<option value='' selected>Todos os cultivos</option>";
      selectMane.innerHTML = "<option value='' selected>Todos os tipos de manejo</option>";
    }
  }

  // === Força atualização em todos os tipos de interação ===
  ["change", "click", "keyup", "mouseup", "input", "blur", "focusout"].forEach(evt => {
    selectProp.addEventListener(evt, () => setTimeout(atualizar, 200));
  });

  // === Garante atualização até por rolagem ou Tab ===
  selectProp.addEventListener("focus", () => {
    const observer = new MutationObserver(() => setTimeout(atualizar, 200));
    observer.observe(selectProp, { attributes: true, childList: true, subtree: true });
    setTimeout(() => observer.disconnect(), 2000);
  });
});
