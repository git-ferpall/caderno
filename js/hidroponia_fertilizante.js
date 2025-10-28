/**
 * HIDROPONIA_DEFENSIVO.JS v3.0
 * Identifica corretamente estufa_id e área_id (numérico)
 * Compatível com nomes textuais ("Bancada 01")
 * Atualizado em 2025-10-28
 */

document.addEventListener("DOMContentLoaded", () => {

  // === Identifica estufa_id e area_id ===
  document.querySelectorAll('.form-defensivo').forEach(form => {
    const idMatch = form.id.match(/e-(\d+)-b-(.+)-defensivo$/);
    if (idMatch) {
      const estufaId = idMatch[1];
      const bancadaNome = idMatch[2].replace(/-/g, " ").trim();

      const btn = Array.from(document.querySelectorAll(`.item-bancada`))
        .find(b => b.textContent.trim() === bancadaNome);

      let bancadaId = null;

      if (btn) {
        const matchBtn = btn.id.match(/item-bancada-(\d+)-estufa/);
        if (matchBtn) {
          bancadaId = matchBtn[1];
        } else {
          const numMatch = bancadaNome.match(/(\d+)\s*$/);
          if (numMatch) {
            bancadaId = numMatch[1];
          }
        }
      }

      if (bancadaId) {
        form.dataset.estufaId = estufaId;
        form.dataset.areaId = bancadaId;
        console.log(`🧩 Defensivo → Estufa ${estufaId}, Bancada ID ${bancadaId}`);
      } else {
        console.warn(`⚠️ Não foi possível extrair ID numérico da bancada "${bancadaNome}"`);
      }
    } else {
      console.warn("⚠️ Não foi possível identificar estufa/bancada para o formulário:", form.id);
    }
  });

  // === Botão "Salvar" ===
  document.querySelectorAll('.form-defensivo .salvar-defensivo').forEach(btn => {
    btn.addEventListener('click', async e => {
      e.preventDefault();
      const form = btn.closest('.form-defensivo');
      const estufa_id = form.dataset.estufaId;
      const area_id = form.dataset.areaId;

      const produto = form.querySelector('input[name="produto"]')?.value.trim() || "";
      const dose = form.querySelector('input[name="dose"]')?.value.trim() || "";
      const motivo = form.querySelector('input[name="motivo"]:checked')?.value || "";
      const obs = form.querySelector('textarea[name="observacoes"]')?.value.trim() || "";

      if (!area_id || !estufa_id) {
        alert("Erro interno: área ou estufa não identificada.");
        console.warn("Form sem dataset:", form);
        return;
      }
      if (!produto) {
        alert("Informe o produto aplicado.");
        return;
      }

      try {
        console.log("💾 Enviando defensivo:", { estufa_id, area_id, produto, dose, motivo, obs });

        const resp = await fetch("../funcoes/salvar_defensivo_hidroponia.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            estufa_id,
            area_id,
            produto,
            dose,
            motivo,
            obs
          })
        });

        const data = await resp.json();
        console.log("📦 Resposta salvar defensivo:", data);

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

  // === Botão "Cancelar" ===
  document.querySelectorAll('.form-defensivo .form-cancel').forEach(btn => {
    btn.addEventListener('click', () => {
      const form = btn.closest('.form-defensivo');
      form.classList.add('d-none');
    });
  });
});
