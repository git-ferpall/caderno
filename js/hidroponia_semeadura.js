/**
 * HIDROPONIA_SEMEADURA.JS
 * Registra semeaduras por bancada
 */

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".form-semeadura").forEach(form => {
    const id = form.id;
    const match = id.match(/e-(\d+)-b-(.+)-semeadura$/);
    if (match) {
      form.dataset.estufaId = match[1];
      form.dataset.bancadaNome = match[2].trim();
    }

    const dataInput = form.querySelector('input[type="date"]');
    if (dataInput && !dataInput.value) {
      dataInput.value = new Date().toISOString().slice(0, 10);
    }
  });

  document.querySelectorAll(".form-semeadura .form-save").forEach(btn => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();

      const form = btn.closest(".form-semeadura");
      const estufa_id = form.dataset.estufaId;
      const area_id = form.dataset.bancadaNome;

      const data = form.querySelector('input[type="date"]')?.value || "";
      const variedade = form.querySelector('input[id*="-variedade"]')?.value.trim() || "";
      const tipoSemeadura = form.querySelector('select[id*="-tipo"]')?.value || "";
      const qtd = form.querySelector('input[id*="-qtd"]')?.value.trim() || "";
      const unidade = form.querySelector('select[id*="-unidade"]')?.value || "sementes";
      const obs = form.querySelector('textarea[id*="-obs"]')?.value.trim() || "";
      const cultivo = form.querySelector('select[id*="-cultivo"]')?.value || "";
      const status = form.querySelector('select[id*="-status"]')?.value || "";

      if (!estufa_id || !area_id) {
        alert("Erro interno: estufa ou bancada não identificada.");
        return;
      }
      if (!data || !tipoSemeadura || !qtd) {
        alert("Preencha data, tipo de semeadura e quantidade.");
        return;
      }
      if (!status) {
        alert("Selecione se o manejo está concluído ou pendente.");
        return;
      }

      try {
        const fd = new FormData();
        fd.append("estufa_id", estufa_id);
        fd.append("area_id", area_id);
        fd.append("data", data);
        fd.append("variedade", variedade);
        fd.append("tipo_semeadura", tipoSemeadura);
        fd.append("quantidade", qtd);
        fd.append("unidade", unidade);
        fd.append("obs", obs);
        fd.append("status", status);
        if (cultivo) fd.append("cultivo_produto_id", cultivo);

        if (typeof CadernoSalvar !== "undefined") {
          await CadernoSalvar.postFormData("salvar_semeadura_hidroponia.php", fd, {
            redirect: false,
            onSuccess: () => form.classList.add("d-none"),
            onError: (d) => alert("❌ " + (d?.err || "Erro ao registrar semeadura.")),
          });
        }
      } catch (err) {
        console.error(err);
        alert("Falha na comunicação com o servidor.");
      }
    });
  });

  document.querySelectorAll(".form-semeadura .form-cancel").forEach(btn => {
    btn.addEventListener("click", () => {
      btn.closest(".form-semeadura")?.classList.add("d-none");
    });
  });
});
