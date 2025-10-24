// js/bancada.js
export function inicializarBancadas() {
  console.log("✅ Módulo de bancadas inicializado");

  // Alternar exibição de formulário de nova bancada
  document.addEventListener("click", (e) => {
    const btnAdd = e.target.closest(".btn-alter-item");
    if (btnAdd && btnAdd.id.startsWith("bancada-add-estufa-")) {
      const estufaId = btnAdd.id.replace("bancada-add-estufa-", "");
      const formBox = document.getElementById(`item-add-bancada-estufa-${estufaId}`);
      formBox?.classList.toggle("d-none");
    }

    const btnCancel = e.target.closest(".form-cancel");
    if (btnCancel && btnCancel.id.startsWith("form-cancel-bancada-estufa-")) {
      const estufaId = btnCancel.id.replace("form-cancel-bancada-estufa-", "");
      const formBox = document.getElementById(`item-add-bancada-estufa-${estufaId}`);
      formBox?.classList.add("d-none");
    }
  });

  // Salvar nova bancada
  document.addEventListener("click", async (e) => {
    const btnSave = e.target.closest(".form-save");
    if (btnSave && btnSave.id.startsWith("form-save-bancada-estufa-")) {
      const estufaId = btnSave.id.replace("form-save-bancada-estufa-", "");
      const nome = document.querySelector(`#item-add-bancada-estufa-${estufaId} #b-nome`)?.value.trim();
      const cultura = document.querySelector(`#item-add-bancada-estufa-${estufaId} #b-area`)?.value.trim();
      const obs = document.querySelector(`#item-add-bancada-estufa-${estufaId} #b-obs`)?.value.trim();

      if (!nome) {
        alert("Informe o nome/número da bancada!");
        return;
      }

      try {
        const resp = await fetch("../funcoes/add_bancada.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            estufa_id: estufaId,
            nome,
            cultura,
            obs,
          }),
        });

        const data = await resp.json();
        if (data.ok) {
          alert("✅ Bancada adicionada com sucesso!");
          location.reload(); // ou recarregar via carregarEstufas()
        } else {
          alert("Erro: " + (data.err || "Falha ao salvar."));
        }
      } catch (err) {
        console.error("Erro ao adicionar bancada:", err);
        alert("Erro ao adicionar bancada.");
      }
    }
  });
}
