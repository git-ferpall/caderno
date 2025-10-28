/**
 * HIDROPONIA_DEFENSIVO.JS v3.1
 * Totalmente compatível com IDs PHP literais (<?php echo $form_id; ?>)
 * Detecta automaticamente estufa_id e área_id sem alterar o HTML
 * Atualizado em 2025-10-28
 */

document.addEventListener("DOMContentLoaded", () => {

  // === Identifica estufa_id e area_id ===
  document.querySelectorAll(".form-defensivo").forEach((form) => {
    let formId = form.id;
    let estufaId = null;
    let areaId = null;

    // 🧩 Caso o ID contenha PHP literal (“<?php ... ?>”), faz fallback usando o DOM
    if (formId.includes("<?php")) {
      const container = form.closest(".item-bancada-content");
      if (container) {
        const estufaMatch = container.id.match(/estufa-(\d+)/);
        if (estufaMatch) estufaId = estufaMatch[1];

        // tenta achar o número da bancada no id do botão ou no texto
        const btn = container.previousElementSibling?.querySelector(".item-bancada-title");
        if (btn) {
          const numMatch = btn.textContent.match(/(\d+)\s*$/);
          if (numMatch) areaId = numMatch[1];
        }
      }
      if (estufaId) form.dataset.estufaId = estufaId;
      if (areaId) form.dataset.areaId = areaId;

      console.log(
        `🧩 (Fallback PHP literal) Estufa ${form.dataset.estufaId || "?"}, Bancada ${form.dataset.areaId || "?"}`
      );
      return;
    }

    // 🧩 Caso o ID já esteja renderizado corretamente (ex: add-e-1-b-Bancada 01-defensivo)
    const idMatch = formId.match(/e-(\d+)-b-(.+)-defensivo$/);
    if (idMatch) {
      estufaId = idMatch[1];
      const bancadaNome = idMatch[2].replace(/-/g, " ").trim();

      const btn = Array.from(document.querySelectorAll(".item-bancada")).find(
        (b) => b.textContent.trim() === bancadaNome
      );

      if (btn) {
        const matchBtn = btn.id.match(/item-bancada-(\d+)-estufa/);
        if (matchBtn) {
          areaId = matchBtn[1];
        } else {
          const numMatch = bancadaNome.match(/(\d+)\s*$/);
          if (numMatch) areaId = numMatch[1];
        }
      }

      if (estufaId) form.dataset.estufaId = estufaId;
      if (areaId) form.dataset.areaId = areaId;

      console.log(
        `🧩 Defensivo → Estufa ${form.dataset.estufaId || "?"}, Bancada ID ${form.dataset.areaId || "?"}`
      );
    } else {
      console.warn("⚠️ Form defensivo com ID inesperado:", formId);
    }
  });

  // === Botão "Salvar" ===
  document.querySelectorAll(".form-defensivo .form-save").forEach((btn) => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();

      const form = btn.closest(".form-defensivo");
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
        console.log("💾 Enviando defensivo:", {
          estufa_id,
          area_id,
          produto,
          dose,
          motivo,
          obs,
        });

        const resp = await fetch("../funcoes/salvar_defensivo_hidroponia.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            estufa_id,
            area_id,
            produto,
            dose,
            motivo,
            obs,
          }),
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
  document.querySelectorAll(".form-defensivo .form-cancel").forEach((btn) => {
    btn.addEventListener("click", () => {
      const form = btn.closest(".form-defensivo");
      form.classList.add("d-none");
    });
  });
});
