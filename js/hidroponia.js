document.addEventListener("DOMContentLoaded", () => {
  /* =======================================================
     BOTÃO: NOVA ESTUFA
  ======================================================= */
  const btnAddEstufa = document.getElementById("form-save-estufa");
  if (btnAddEstufa) {
    btnAddEstufa.addEventListener("click", async () => {
      const nome = document.getElementById("e-nome").value.trim();
      const area = document.getElementById("e-area").value.trim();
      const obs = document.getElementById("e-obs").value.trim();

      if (!nome) {
        alert("Informe o nome da estufa!");
        return;
      }

      const data = new FormData();
      data.append("nome", nome);
      data.append("area_m2", area);
      data.append("observacoes", obs);

      try {
        const r = await fetch("../funcoes/add_estufa.php", {
          method: "POST",
          body: data,
        });
        const j = await r.json();
        if (j.ok) {
          alert("Estufa cadastrada com sucesso!");
          location.reload();
        } else {
          alert("Erro ao cadastrar: " + (j.err || "desconhecido"));
        }
      } catch (e) {
        alert("Falha ao conectar com o servidor.");
      }
    });
  }

  /* =======================================================
     BOTÃO: NOVA BANCADA
     (vincula uma área já existente à estufa selecionada)
  ======================================================= */
  const btnsSalvarBancada = document.querySelectorAll("[id^='form-save-bancada-estufa-']");
  btnsSalvarBancada.forEach((btn) => {
    btn.addEventListener("click", async (e) => {
      const estufaId = e.target.id.replace("form-save-bancada-estufa-", "");

      const nome = document.getElementById("b-nome").value.trim();
      const cultura = document.getElementById("b-area").value.trim();
      const obs = document.getElementById("b-obs").value.trim();

      if (!nome) {
        alert("Informe o nome da bancada!");
        return;
      }

      try {
        // 1️⃣ cadastra a nova área (bancada)
        const areaData = new FormData();
        areaData.append("nome", nome);
        areaData.append("tipo", "estufa");
        areaData.append("observacoes", obs);
        areaData.append("cultura", cultura);

        const areaResponse = await fetch("../funcoes/add_area.php", {
          method: "POST",
          body: areaData,
        });

        const areaJson = await areaResponse.json();
        if (!areaJson.ok) {
          alert("Erro ao cadastrar área: " + (areaJson.err || ""));
          return;
        }

        // 2️⃣ vincula a área à estufa
        const vinculoData = new FormData();
        vinculoData.append("estufa_id", estufaId);
        vinculoData.append("area_id", areaJson.id);

        const vinculoResponse = await fetch("../funcoes/vincular_area_estufa.php", {
          method: "POST",
          body: vinculoData,
        });
        const vinculoJson = await vinculoResponse.json();

        if (vinculoJson.ok) {
          alert("Bancada vinculada à estufa com sucesso!");
          location.reload();
        } else {
          alert("Erro ao vincular bancada: " + (vinculoJson.err || ""));
        }
      } catch (e) {
        console.error(e);
        alert("Erro inesperado ao salvar bancada.");
      }
    });
  });

  /* =======================================================
     EXEMPLO: SELECIONAR ESTUFA
     (abre o painel de detalhes)
  ======================================================= */
  window.selectEstufa = function (id) {
    document.querySelectorAll(".item-estufa-box").forEach((box) => box.classList.add("d-none"));
    const box = document.getElementById(`estufa-${id}-box`);
    if (box) box.classList.remove("d-none");
  };

  /* =======================================================
     EXEMPLO: VOLTAR DA BANCADA
  ======================================================= */
  window.voltarEstufa = function (id) {
    const box = document.getElementById(`estufa-${id}-box`);
    if (box) box.classList.remove("d-none");
    document.querySelectorAll(".item-bancada-content").forEach((el) => el.classList.add("d-none"));
  };

  /* =======================================================
     EXEMPLO: SELECIONAR BANCADA
  ======================================================= */
  window.selectBancada = function (bancadaNome, estufaId) {
    document.querySelectorAll(".item-bancada-content").forEach((el) => el.classList.add("d-none"));
    const content = document.getElementById(`item-bancada-${bancadaNome}-content-estufa-${estufaId}`);
    if (content) content.classList.remove("d-none");
  };
});
