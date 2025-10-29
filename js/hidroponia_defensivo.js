/**
 * HIDROPONIA_DEFENSIVO.JS v4.0
 * Funciona mesmo com forms que usam <?php echo $form_id; ?> literal
 * Detecta estufa e bancada via contexto DOM
 * Baseado no padrão hidroponia_fertilizante.js v3.9
 */

document.addEventListener("DOMContentLoaded", () => {
  // === Vincula forms automaticamente (mesmo com <?php echo ... ?> literal) ===
  document.querySelectorAll(".form-defensivo").forEach((form, i) => {
    let estufaId = null;
    let bancadaNome = null;

    // 1️⃣ tenta deduzir pelo botão de defensivo correspondente
    const parentBtn = form.closest(".item-bancada-content")?.querySelector(".bancada-defensivo");
    if (parentBtn && parentBtn.id) {
      const match = parentBtn.id.match(/item-bancada-(.+?)-estufa-(\d+)-defensivo/);
      if (match) {
        bancadaNome = match[1].trim();
        estufaId = match[2];
      }
    }

    // 2️⃣ fallback — tenta pegar pelo item-bancada-content
    if (!estufaId) {
      const container = form.closest(".item-bancada-content");
      if (container && container.id) {
        const match = container.id.match(/item-bancada-(.+?)-content-estufa-(\d+)/);
        if (match) {
          bancadaNome = match[1].trim();
          estufaId = match[2];
        }
      }
    }

    // 3️⃣ define dataset se achou
    if (estufaId && bancadaNome) {
      const numMatch = bancadaNome.match(/(\d+)$/);
      form.dataset.estufaId = estufaId;
      form.dataset.areaId = numMatch ? numMatch[1] : bancadaNome;
      console.log(`🧩 Vinculado form defensivo #${i + 1} → estufa=${estufaId}, area=${form.dataset.areaId}`);
    } else {
      console.warn("⚠️ Formulário defensivo sem correspondência clara:", form);
    }
  });

  // === Carrega inseticidas ===
  async function carregarInseticidas() {
    try {
      console.log("🔄 Carregando inseticidas...");
      const resp = await fetch("../funcoes/buscar_inseticidas.php", { headers: { "Cache-Control": "no-cache" } });
      let data = await resp.text();

      try { data = JSON.parse(data); }
      catch { console.warn("⚠️ Retorno não era JSON:", data); return; }

      if (!Array.isArray(data)) { console.warn("⚠️ Resposta inesperada:", data); return; }

      console.log(`✅ ${data.length} inseticidas carregados.`);

      document.querySelectorAll('.form-defensivo select[id*="-produto"]').forEach(sel => {
        sel.innerHTML = '<option value="">Selecione o inseticida</option>';
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
      console.error("❌ Erro ao carregar inseticidas:", err);
    }
  }

  carregarInseticidas();

  // Recarrega quando abre o form
  document.addEventListener("click", (e) => {
    if (e.target.closest(".bancada-defensivo")) setTimeout(carregarInseticidas, 400);
  });

  // === Campo "Outro" ===
  document.addEventListener("change", (e) => {
    if (e.target.matches('.form-defensivo select[id*="-produto"]')) {
      const sel = e.target;
      const form = sel.closest(".form-defensivo");
      const inputOutro = form.querySelector(".defensivo-outro");
      inputOutro.style.display = (sel.value === "outro") ? "block" : "none";
    }
  });

  // === Salvar ===
  document.querySelectorAll(".form-defensivo .form-save").forEach(btn => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();
      const form = btn.closest(".form-defensivo");
      const estufa_id = form.dataset.estufaId;
      const area_id = form.dataset.areaId;
      const produtoSel = form.querySelector('select[id*="-produto"]');
      const produto_id = produtoSel?.value || "";
      const outroInput = form.querySelector(".defensivo-outro");
      const produto_outro = (produto_id === "outro") ? outroInput.value.trim() : "";
      const dose = form.querySelector('input[id*="-dose"]')?.value.trim() || "";
      const motivo = form.querySelector('input[name*="motivo"]:checked')?.value || "";
      const obs = form.querySelector('textarea[id*="-obs"]')?.value.trim() || "";

      if (!area_id) {
        alert("Erro interno: área não identificada.");
        console.warn("🧩 area_id ausente:", { estufa_id, area_id });
        return;
      }
      if (!produto_id) {
        alert("Selecione o inseticida aplicado.");
        return;
      }

      console.log("💾 Salvando defensivo:", { estufa_id, area_id, produto_id, produto_outro, dose, motivo, obs });

      try {
        const resp = await fetch("../funcoes/salvar_defensivo_hidroponia.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            estufa_id,
            area_id,
            produto_id,
            produto_outro,
            dose,
            motivo,
            obs,
          }),
        });

        const data = await resp.json();
        console.log("📦 Resposta do servidor:", data);

        if (data.ok) {
          form.classList.add("d-none");
          location.reload();
        } else {
          alert("❌ " + (data.err || "Erro ao salvar defensivo."));
        }
      } catch (err) {
        console.error("❌ Erro na comunicação com o servidor:", err);
        alert("Falha na comunicação com o servidor.");
      }
    });
  });

  // === Cancelar ===
  document.querySelectorAll(".form-defensivo .form-cancel").forEach(btn => {
    btn.addEventListener("click", () => btn.closest(".form-defensivo").classList.add("d-none"));
  });
});
