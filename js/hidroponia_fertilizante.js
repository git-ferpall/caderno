/**
 * HIDROPONIA_FERTILIZANTE.JS v3.4
 * Compatível com HTML existente (sem alterar forms)
 * Detecta automaticamente estufa_id e área_id pelo ID do form
 * Corrige input "Dose utilizada" bloqueado
 * Atualizado em 2025-10-28
 */

document.addEventListener("DOMContentLoaded", () => {

  // === Autoatribui data-estufa-id e data-area-id com base no ID do form ===
  document.querySelectorAll(".form-fertilizante").forEach(form => {
    const id = form.id; // ex: add-e-1-b-Bancada 01-fertilizante
    const match = id.match(/e-(\d+)-b-(.+)-fertilizante$/);

    if (match) {
      const estufaId = match[1];
      const bancadaNome = match[2].trim();
      form.dataset.estufaId = estufaId;

      // tenta extrair número no final do nome da bancada
      const numMatch = bancadaNome.match(/(\d+)$/);
      form.dataset.areaId = numMatch ? numMatch[1] : bancadaNome;

      console.log(`🧩 Vinculado form ${id} → estufa=${estufaId}, area=${form.dataset.areaId}`);
    } else {
      console.warn("⚠️ Formulário sem padrão esperado:", id);
    }
  });

  // === Corrige inputs bloqueados (ex: 'Dose utilizada') ===
  document.querySelectorAll('.form-fertilizante input[id*="-dose"]').forEach(inp => {
    inp.removeAttribute('readonly');
    inp.removeAttribute('disabled');
    inp.type = "text";
    inp.style.pointerEvents = "auto";
  });

  // === Função para carregar todos os fertilizantes ===
  async function carregarFertilizantes() {
    try {
      console.log("🔄 Carregando fertilizantes...");
      const resp = await fetch("../funcoes/buscar_fertilizantes.php", {
        headers: { "Cache-Control": "no-cache" }
      });
      let data = await resp.text();

      try {
        data = JSON.parse(data);
      } catch {
        console.warn("⚠️ Retorno não era JSON, conteúdo recebido:", data);
        return;
      }

      if (!Array.isArray(data)) {
        console.warn("⚠️ Resposta inesperada, não é lista:", data);
        return;
      }

      console.log(`✅ ${data.length} fertilizantes carregados.`);

      // Fallback de seletor
      const selects = document.querySelectorAll(
        '.form-fertilizante select[id*="-produto"], ' +
        '.form-fertilizante select[name*="produto"], ' +
        '.form-fertilizante select'
      );

      if (!selects.length) {
        console.warn("⚠️ Nenhum <select> encontrado em .form-fertilizante");
        return;
      }

      selects.forEach(sel => {
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
      alert("Erro ao carregar lista de fertilizantes. Veja o console.");
    }
  }

  // --- Carrega inicialmente ---
  carregarFertilizantes();

  // --- Recarrega lista ao abrir formulário ---
  document.addEventListener("click", (e) => {
    if (e.target.closest(".bancada-fertilizante")) {
      setTimeout(carregarFertilizantes, 400);
    }
  });

  // === Exibir campo "Outro" ===
  document.addEventListener("change", (e) => {
    if (e.target.matches('.form-fertilizante select[id*="-produto"], .form-fertilizante select[name*="produto"]')) {
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

  // === Botão "Salvar" ===
  document.querySelectorAll(".form-fertilizante .form-save").forEach(btn => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();

      const form = btn.closest(".form-fertilizante");
      const estufa_id = form.dataset.estufaId;
      const area_id = form.dataset.areaId;

      const produtoSel = form.querySelector('select[id*="-produto"], select[name*="produto"]');
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
        console.warn("🧩 Dados ausentes:", { estufa_id, area_id, form });
        return;
      }

      if (!produto_id && !produtoFinal) {
        alert("Selecione ou digite o fertilizante.");
        return;
      }

      try {
        console.log("💾 Salvando fertilizante:", {
          estufa_id,
          area_id,
          produto_id,
          dose,
          tipo,
          obs,
        });

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

  // === Botão "Cancelar" ===
  document.querySelectorAll(".form-fertilizante .form-cancel").forEach(btn => {
    btn.addEventListener("click", () => {
      const form = btn.closest(".form-fertilizante");
      form.classList.add("d-none");
    });
  });
});
