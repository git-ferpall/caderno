/**
 * HIDROPONIA_FERTILIZANTE.JS v3.9
 * Detecta automaticamente estufa_id e area_id (sem alterar HTML)
 * Mantém compatibilidade total com hidroponia.js v2.5
 * Atualizado em 2025-10-28
 */

document.addEventListener("DOMContentLoaded", () => {

  // === Vincula forms automaticamente com estufa/área ===
  document.querySelectorAll(".form-fertilizante").forEach(form => {
    const id = form.id; // ex: add-e-1-b-Bancada 04-fertilizante
    const match = id.match(/e-(\d+)-b-(.+)-fertilizante$/);

    if (match) {
      const estufaId = match[1];
      const bancadaNome = match[2].trim();
      form.dataset.estufaId = estufaId;

      const numMatch = bancadaNome.match(/(\d+)$/);
      form.dataset.areaId = numMatch ? numMatch[1] : bancadaNome;

      console.log(`🧩 Vinculado form ${id} → estufa=${estufaId}, area=${form.dataset.areaId}`);
    } else {
      console.warn("⚠️ Formulário fora do padrão esperado:", id);
    }
  });

  // === Corrige inputs travados ===
  document.querySelectorAll('.form-fertilizante input[id*="-dose"]').forEach(inp => {
    inp.removeAttribute("readonly");
    inp.removeAttribute("disabled");
    inp.style.pointerEvents = "auto";
  });

  // === Detecta area_id pela bancada selecionada, sem mudar HTML ===
  function getAreaIdFromSelectedBancada(estufa_id) {
    const bancadaSel = document.querySelector(`.item-bancada.bancada-selecionada[id*="estufa-${estufa_id}"]`);
    if (!bancadaSel) return null;

    const nomeMatch = bancadaSel.id.match(/item-bancada-(.+?)-estufa-(\d+)/);
    if (!nomeMatch) return null;
    const nomeBancada = nomeMatch[1].trim();

    // procura form relacionado
    const formMatch = Array.from(document.querySelectorAll(`.form-fertilizante[id*="e-${estufa_id}-b-"]`))
      .find(f => f.id.includes(nomeBancada));

    if (formMatch) {
      const datasetArea = formMatch.dataset.areaId;
      if (datasetArea && !isNaN(datasetArea)) return datasetArea;

      const numMatch = nomeBancada.match(/(\d+)$/);
      if (numMatch) return numMatch[1];
    }

    const numMatch = nomeBancada.match(/(\d+)$/);
    return numMatch ? numMatch[1] : null;
  }

  // === Carrega fertilizantes ===
  async function carregarFertilizantes() {
    try {
      console.log("🔄 Carregando fertilizantes...");
      const resp = await fetch("../funcoes/buscar_fertilizantes.php", { headers: { "Cache-Control": "no-cache" } });
      let data = await resp.text();

      try { data = JSON.parse(data); }
      catch { console.warn("⚠️ Retorno não era JSON:", data); return; }

      if (!Array.isArray(data)) { console.warn("⚠️ Resposta inesperada:", data); return; }

      console.log(`✅ ${data.length} fertilizantes carregados.`);

      document.querySelectorAll('.form-fertilizante select[id*="-produto"]').forEach(sel => {
        sel.innerHTML = '<option value="">Selecione o fertilizante</option>';
        data.forEach(item => {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = item.nome;
          sel.appendChild(opt);
        });
        const outro = document.createElement("option");
        outro.value = "outro";
        outro.textContent = "Outro (digitar manualmente)";
        sel.appendChild(outro);
      });
    } catch (err) {
      console.error("❌ Erro ao carregar fertilizantes:", err);
    }
  }

  carregarFertilizantes();

  // Recarrega ao abrir o form
  document.addEventListener("click", (e) => {
    if (e.target.closest(".bancada-fertilizante")) setTimeout(carregarFertilizantes, 400);
  });

  // === Campo "Outro" ===
  document.addEventListener("change", (e) => {
    if (e.target.matches('.form-fertilizante select[id*="-produto"]')) {
      const sel = e.target;
      const form = sel.closest(".form-fertilizante");

      let inputOutro = form.querySelector(".fertilizante-outro");
      if (!inputOutro) {
        inputOutro = document.createElement("input");
        inputOutro.type = "text";
        inputOutro.className = "form-text fertilizante-outro";
        inputOutro.placeholder = "Digite o nome do fertilizante";
        inputOutro.style.marginTop = "8px";
        inputOutro.style.display = "none";
        sel.insertAdjacentElement("afterend", inputOutro);
      }
      inputOutro.style.display = (sel.value === "outro") ? "block" : "none";
    }
  });

  // === Salvar ===
  document.querySelectorAll(".form-fertilizante .form-save").forEach(btn => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();

      const form = btn.closest(".form-fertilizante");
      const estufa_id = form.dataset.estufaId;
      let area_id = form.dataset.areaId;

      if (!area_id) {
        area_id = getAreaIdFromSelectedBancada(estufa_id);
        console.log(`🧭 area_id obtido via bancada selecionada: ${area_id}`);
      }

      const produtoSel = form.querySelector('select[id*="-produto"]');
      const produto_id = produtoSel?.value || "";
      const produtoNome = produtoSel?.options[produtoSel.selectedIndex]?.text.trim() || "";
      const outroInput = form.querySelector(".fertilizante-outro");
      const produtoFinal = (produto_id === "outro" && outroInput)
        ? outroInput.value.trim()
        : produtoNome;

      const dose = form.querySelector('input[id*="-dose"]')?.value.trim() || "";
      const tipo = form.querySelector('input[name*="tipo"]:checked')?.value || "";
      const obs = form.querySelector('textarea[id*="-obs"]')?.value.trim() || "";

      if (!area_id || !estufa_id) {
        alert("Erro interno: área ou estufa não identificada.");
        console.warn("🧩 Dados ausentes:", { estufa_id, area_id });
        return;
      }
      if (!produto_id && !produtoFinal) {
        alert("Selecione ou digite o fertilizante.");
        return;
      }

      console.log("💾 Salvando fertilizante:", { estufa_id, area_id, produto_id, dose, tipo, obs });

      try {
        const resp = await fetch("../funcoes/salvar_fertilizante_hidroponia.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            estufa_id,
            area_id,
            produto_id: produto_id === "outro" ? 0 : produto_id,
            dose,
            tipo,
            obs,
          }),
        });

        const data = await resp.json();
        console.log("📦 Resposta do servidor:", data);

        if (data.ok) {
          form.classList.add("d-none");
          location.reload();
        } else {
          alert("❌ " + (data.err || "Erro ao salvar fertilizante."));
        }
      } catch (err) {
        console.error("❌ Erro na comunicação com o servidor:", err);
        alert("Falha na comunicação com o servidor.");
      }
    });
  });

  // === Cancelar ===
  document.querySelectorAll(".form-fertilizante .form-cancel").forEach(btn => {
    btn.addEventListener("click", () => btn.closest(".form-fertilizante").classList.add("d-none"));
  });
});
