/**
 * HIDROPONIA_COLHEITA.JS v1.1
 * Caderno de Campo - Frutag
 * Registra colheitas por bancada (sem alterar HTML)
 * Atualizado em 2025-10-29
 */

document.addEventListener("DOMContentLoaded", () => {
  // === Detecta automaticamente cada form de colheita ===
  document.querySelectorAll(".form-colheita").forEach(form => {
    const id = form.id; // ex: add-e-1-b-Bancada 01-colheita
    const match = id.match(/e-(\d+)-b-(.+)-colheita$/);

    if (match) {
      const estufaId = match[1];
      const bancadaNome = match[2].trim();
      form.dataset.estufaId = estufaId;
      form.dataset.bancadaNome = bancadaNome;
      console.log(`🥬 Vinculado form ${id} → estufa=${estufaId}, bancada=${bancadaNome}`);
    } else {
      console.warn("⚠️ Formulário fora do padrão esperado:", id);
    }
  });

  // === Botão "Salvar" ===
  document.querySelectorAll(".form-colheita .form-save").forEach(btn => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();

      const form = btn.closest(".form-colheita");
      const estufa_id = form.dataset.estufaId;
      const area_id = form.dataset.bancadaNome;

      const qtd = form.querySelector('input[id*="-qtd"]')?.value.trim() || "";
      const destino = form.querySelector('input[name*="-dest"]:checked')?.value || "";
      const obs = form.querySelector('textarea[id*="-obs"]')?.value.trim() || "";

      if (!estufa_id || !area_id) {
        alert("Erro interno: estufa ou bancada não identificada.");
        console.warn("🧩 Dados ausentes:", { estufa_id, area_id, form });
        return;
      }

      if (!qtd) {
        alert("Informe a quantidade colhida.");
        return;
      }

      try {
        console.log("💾 Enviando colheita:", {
          estufa_id,
          area_id,
          quantidade: qtd,
          destino,
          obs
        });

        const resp = await fetch("../funcoes/salvar_colheita_hidroponia.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            estufa_id,
            area_id,
            quantidade: qtd,
            destino,
            obs
          })
        });

        const data = await resp.json();
        console.log("📦 Resposta do servidor:", data);

        if (data.ok) {
          form.classList.add("d-none"); // ✅ Só oculta o form, sem alert
        } else {
          alert("❌ " + (data.err || "Erro ao registrar colheita."));
        }
      } catch (err) {
        console.error("❌ Erro na comunicação com o servidor:", err);
        alert("Falha na comunicação com o servidor.");
      }
    });
  });

  // === Botão "Cancelar" ===
  document.querySelectorAll(".form-colheita .form-cancel").forEach(btn => {
    btn.addEventListener("click", () => {
      const form = btn.closest(".form-colheita");
      form.classList.add("d-none");
    });
  });
});
