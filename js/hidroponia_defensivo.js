/**
 * HIDROPONIA_DEFENSIVO.JS v4.2
 * Caderno de Campo - Frutag
 * Aplica defensivos por bancada (sem alterar HTML)
 * Padrão colheita (usa SELECT na tabela bancadas)
 * Atualizado em 2025-10-29
 */

document.addEventListener("DOMContentLoaded", () => {

  // === Detecta automaticamente cada form de defensivo ===
  document.querySelectorAll(".form-defensivo").forEach(form => {
    const id = form.id; // ex: add-e-1-b-Bancada 01-defensivo
    const match = id.match(/e-(\d+)-b-(.+)-defensivo$/);

    if (match) {
      const estufaId = match[1];
      const bancadaNome = match[2].trim();
      form.dataset.estufaId = estufaId;
      form.dataset.bancadaNome = bancadaNome;
      console.log(`🧩 Vinculado form ${id} → estufa=${estufaId}, bancada=${bancadaNome}`);
    } else {
      console.warn("⚠️ Formulário fora do padrão esperado:", id);
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
        sel.innerHTML = '<option value="">Selecione o produto aplicado</option>';
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

  // === Campo "Outro" ===
  document.addEventListener("change", (e) => {
    if (e.target.matches('.form-defensivo select[id*="-produto"]')) {
      const sel = e.target;
      const form = sel.closest(".form-defensivo");
      const outroInput = form.querySelector(".defensivo-outro");
      outroInput.style.display = (sel.value === "outro") ? "block" : "none";
    }
  });

  // === Botão "Salvar" ===
  document.querySelectorAll(".form-defensivo .form-save").forEach(btn => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();

      const form = btn.closest(".form-defensivo");
      const estufa_id = form.dataset.estufaId;
      const bancada_nome = form.dataset.bancadaNome;

      const produtoSel = form.querySelector('select[id*="-produto"]');
      const produto_id = produtoSel?.value || "";
      const produto_outro = form.querySelector(".defensivo-outro")?.value.trim() || "";
      const dose = form.querySelector('input[id*="-dose"]')?.value.trim() || "";
      const motivo = form.querySelector('input[name*="motivo"]:checked')?.value || "";
      const obs = form.querySelector('textarea[id*="-obs"]')?.value.trim() || "";

      if (!bancada_nome || !estufa_id) {
        alert("Erro interno: estufa ou bancada não identificada.");
        console.warn("🧩 Dados ausentes:", { estufa_id, bancada_nome });
        return;
      }

      if (!produto_id) {
        alert("Selecione o produto aplicado.");
        return;
      }

      try {
        console.log("💾 Enviando aplicação de defensivo:", {
          estufa_id,
          bancada_nome,
          produto_id,
          produto_outro,
          dose,
          motivo,
          obs
        });

        const fd = new FormData();
        fd.append("estufa_id", estufa_id);
        fd.append("area_id", bancada_nome);
        fd.append("produto_id", produto_id);
        fd.append("produto_outro", produto_outro);
        fd.append("dose", dose);
        fd.append("motivo", motivo);
        fd.append("obs", obs);
        if (typeof CadernoSalvar !== "undefined") {
          await CadernoSalvar.postFormData("salvar_defensivo_hidroponia.php", fd, {
            redirect: false,
            onSuccess: () => form.classList.add("d-none"),
            onError: (d) => alert("❌ " + (d?.err || "Erro ao registrar defensivo.")),
          });
        }
      } catch (err) {
        console.error("❌ Erro na comunicação com o servidor:", err);
        alert("Falha na comunicação com o servidor.");
      }
    });
  });

  // === Botão "Cancelar" ===
  document.querySelectorAll(".form-defensivo .form-cancel").forEach(btn => {
    btn.addEventListener("click", () => {
      const form = btn.closest(".form-defensivo");
      form.classList.add("d-none");
    });
  });
});
