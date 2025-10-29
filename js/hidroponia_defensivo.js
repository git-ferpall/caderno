/**
 * HIDROPONIA_DEFENSIVO.JS
 * Lida com carregamento e envio de defensivos (inseticidas)
 */

document.addEventListener("DOMContentLoaded", () => {
  carregarInseticidas();

  // === Detecta forms ===
  document.querySelectorAll(".form-defensivo").forEach(form => {
    const id = form.id;
    const match = id.match(/e-(\d+)-b-(.+)-defensivo$/);
    if (match) {
      form.dataset.estufaId = match[1];
      form.dataset.bancadaNome = match[2].trim();
    }
  });

  // === Botão salvar ===
  document.querySelectorAll(".form-defensivo .form-save").forEach(btn => {
    btn.addEventListener("click", async e => {
      e.preventDefault();
      const form = btn.closest(".form-defensivo");
      const estufa_id = form.dataset.estufaId;
      const area_id = form.dataset.bancadaNome;
      const inseticida = form.querySelector("select[id*='-produto']").value;
      const inseticida_outro = form.querySelector(".defensivo-outro")?.value || "";
      const dose = form.querySelector("input[id*='-dose']").value.trim();
      const motivo = form.querySelector("input[name*='-motivo']:checked")?.value || "";
      const obs = form.querySelector("textarea[id*='-obs']").value.trim();

      if (!inseticida || inseticida === "-") {
        alert("Selecione o inseticida ou informe manualmente.");
        return;
      }

      const body = new URLSearchParams({
        estufa_id,
        area_id,
        inseticida,
        inseticida_outro,
        dose,
        motivo,
        obs
      });

      try {
        const resp = await fetch("../funcoes/salvar_defensivo_hidroponia.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body
        });
        const data = await resp.json();
        if (data.ok) form.classList.add("d-none");
        else alert("❌ " + (data.err || "Erro ao registrar defensivo"));
      } catch (err) {
        console.error("Erro ao salvar defensivo:", err);
        alert("Falha na comunicação com o servidor.");
      }
    });
  });

  // === Botão cancelar ===
  document.querySelectorAll(".form-defensivo .form-cancel").forEach(btn => {
    btn.addEventListener("click", () => btn.closest(".form-defensivo").classList.add("d-none"));
  });
});

// === Carregar inseticidas ===
function carregarInseticidas() {
  fetch("../funcoes/buscar_inseticidas.php")
    .then(r => r.json())
    .then(data => {
      document.querySelectorAll(".form-defensivo select[id*='-produto']").forEach(sel => {
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
    })
    .catch(err => console.error("Erro ao carregar inseticidas:", err));
}
