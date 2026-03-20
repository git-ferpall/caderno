document.addEventListener("DOMContentLoaded", () => {
  const selectProp = document.getElementById("ri-propriedade");
  const selectAreas = document.getElementById("ri-areas");
  const inputIni = document.getElementById("ri-ini");
  const inputFim = document.getElementById("ri-fin");
  const btnGerar = document.getElementById("ri-gerar-pdf");
  const loading = document.getElementById("pdf-loading");

  const hoje = new Date();
  const ano = hoje.getFullYear();
  const mes = `${hoje.getMonth() + 1}`.padStart(2, "0");
  const ultimoDia = new Date(ano, hoje.getMonth() + 1, 0).getDate();
  inputIni.value = `${ano}-${mes}-01`;
  inputFim.value = `${ano}-${mes}-${String(ultimoDia).padStart(2, "0")}`;

  async function carregarPropriedades() {
    const resp = await fetch("/funcoes/relatorios/buscar_propriedades_areas.php");
    const data = await resp.json();

    if (!data.ok) {
      throw new Error(data.err || "Falha ao carregar propriedades");
    }

    selectProp.innerHTML = '<option value="">Selecione</option>';
    data.propriedades.forEach((p) => {
      const opt = document.createElement("option");
      opt.value = p.id;
      opt.textContent = p.nome_razao;
      selectProp.appendChild(opt);
    });
  }

  async function carregarAreas(propriedadeId) {
    selectAreas.innerHTML = "";
    if (!propriedadeId) {
      selectAreas.innerHTML = '<span style="color:#777;">Selecione uma propriedade para carregar as areas.</span>';
      return;
    }

    const resp = await fetch(`/funcoes/relatorios/buscar_propriedades_areas.php?propriedade_id=${encodeURIComponent(propriedadeId)}`);
    const data = await resp.json();

    if (!data.ok) {
      throw new Error(data.err || "Falha ao carregar areas");
    }

    if (!data.areas || data.areas.length === 0) {
      selectAreas.innerHTML = '<span style="color:#777;">Nenhuma area encontrada para esta propriedade.</span>';
      return;
    }

    data.areas.forEach((a) => {
      const row = document.createElement("label");
      row.style.display = "flex";
      row.style.alignItems = "center";
      row.style.gap = "8px";
      row.style.padding = "4px 0";
      row.style.cursor = "pointer";

      const checkbox = document.createElement("input");
      checkbox.type = "checkbox";
      checkbox.value = a.id;
      checkbox.className = "ri-area-check";

      const text = document.createElement("span");
      text.textContent = a.nome;

      row.appendChild(checkbox);
      row.appendChild(text);
      selectAreas.appendChild(row);
    });
  }

  selectProp.addEventListener("change", () => {
    carregarAreas(selectProp.value).catch((err) => {
      console.error(err);
      alert("Erro ao carregar areas");
    });
  });

  btnGerar.addEventListener("click", async () => {
    const propriedade = selectProp.value;
    const dataIni = inputIni.value;
    const dataFim = inputFim.value;
    const areas = Array.from(document.querySelectorAll(".ri-area-check:checked")).map((o) => o.value);

    if (!propriedade || !dataIni || !dataFim) {
      alert("Preencha propriedade e periodo.");
      return;
    }

    if (areas.length === 0) {
      alert("Selecione ao menos uma area.");
      return;
    }

    const formData = new FormData();
    formData.append("propriedade", propriedade);
    formData.append("data_ini", dataIni);
    formData.append("data_fim", dataFim);
    areas.forEach((id) => formData.append("area[]", id));

    try {
      loading.style.display = "flex";

      const resp = await fetch("/funcoes/relatorios/pdf_irrigacao.php", {
        method: "POST",
        body: formData
      });

      if (!resp.ok) throw new Error("Erro ao gerar PDF");

      const blob = await resp.blob();
      const url = URL.createObjectURL(blob);
      window.open(url, "_blank");
    } catch (err) {
      console.error(err);
      alert("Falha ao gerar relatorio.");
    } finally {
      loading.style.display = "none";
    }
  });

  carregarPropriedades().catch((err) => {
    console.error(err);
    alert("Erro ao carregar propriedades");
  });
});
