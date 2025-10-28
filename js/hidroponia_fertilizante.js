/**
 * HIDROPONIA_FERTILIZANTE.JS v2.6
 * Corrige o carregamento dos fertilizantes (com debug e fallback)
 * Atualizado em 2025-10-28
 */

document.addEventListener("DOMContentLoaded", () => {

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

      // Preenche todos os selects de fertilizante
      document.querySelectorAll('.form-fertilizante select[id*="-produto"], .form-fertilizante select[name="produto_id"]').forEach(sel => {
        sel.innerHTML = '<option value="">Selecione o fertilizante</option>';

        data.forEach(item => {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = item.nome;
          sel.appendChild(opt);
        });

        // Adiciona a opção "Outro"
        const outro = document.createElement("option");
        outro.value = "outro";
        outro.textContent = "Outro (digitar manualmente)";
        sel.appendChild(outro);
      });

    } catch (err) {
      console.error("❌ Erro ao carregar fertilizantes:", err);
      alert("Erro ao carregar lista de fertilizantes. Verifique o console.");
    }
  }

  carregarFertilizantes();

  // === Exibir campo extra ao escolher "Outro" ===
  document.addEventListener("change", (e) => {
    if (e.target.matches('.form-fertilizante select[id*="-produto"], .form-fertilizante select[name="produto_id"]')) {
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
  document.querySelectorAll('.form-fertilizante .form-save').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();

      const form = btn.closest('.form-fertilizante');
      const estufa_id = form.dataset.estufaId;
      const area_id = form.dataset.areaId;

      const produtoSel = form.querySelector('select[id*="-produto"], select[name="produto_id"]');
      const produto_id = produtoSel?.value || "";
      const produtoNome = produtoSel?.options[produtoSel.selectedIndex]?.text.trim() || "";
      const outroInput = form.querySelector('.fertilizante-outro');
      const produtoFinal = (produto_id === "outro" && outroInput)
        ? outroInput.value.trim()
        : produtoNome;

      const dose = form.querySelector('input[name="dose"]').value.trim();
      const tipo = form.querySelector('input[name="tipo"]:checked')?.value || "";
      const obs = form.querySelector('textarea[name="obs"]').value.trim();

      if (!area_id || !estufa_id) {
        alert("Erro interno: área ou estufa não identificada.");
        return;
      }
      if (!produto_id && !produtoFinal) {
        alert("Selecione ou digite o fertilizante.");
        return;
      }

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
            obs
          })
        });

        const data = await resp.json();
        console.log("📦 Resposta salvar fertilizante:", data);

        if (data.ok) {
          form.classList.add("d-none");
          location.reload();
        } else {
          alert("❌ " + (data.err || "Erro ao salvar fertilizante."));
        }
      } catch (err) {
        console.error("Erro na comunicação com o servidor:", err);
        alert("❌ Falha na comunicação com o servidor.");
      }
    });
  });

  // === Botão "Cancelar" ===
  document.querySelectorAll('.form-fertilizante .form-cancel').forEach(btn => {
    btn.addEventListener('click', () => {
      const form = btn.closest('.form-fertilizante');
      form.classList.add('d-none');
    });
  });
});
