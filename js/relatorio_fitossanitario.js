document.addEventListener("DOMContentLoaded", () => {

  const selectProp = document.getElementById("pf-propriedades");
  const selectArea = document.getElementById("pf-area");

  /* ===============================
  🔹 CARREGAR PROPRIEDADES
  =============================== */

  async function carregarPropriedades() {
    try {

      const resp = await fetch("/funcoes/relatorios/buscar_propriedades_areas.php");

      if (!resp.ok) {
        const txt = await resp.text();
        console.error("Erro HTTP:", txt);
        throw new Error("Erro ao buscar propriedades");
      }

      const data = await resp.json();

      if (!data.ok) throw new Error(data.err || "Erro desconhecido");

      selectProp.innerHTML = '<option value="">Selecione</option>';

      data.propriedades.forEach(p => {
        const opt = document.createElement("option");
        opt.value = p.id;
        opt.textContent = p.nome_razao;
        selectProp.appendChild(opt);
      });

    } catch (err) {
      console.error("❌ Erro propriedades:", err);
      alert("Erro ao carregar propriedades");
    }
  }

  /* ===============================
  🔹 CARREGAR ÁREAS
  =============================== */

  async function carregarAreas(propriedade_id) {

    if (!propriedade_id) {
      selectArea.innerHTML = '<option value="">Todas as áreas</option>';
      return;
    }

    try {

      const resp = await fetch(`/funcoes/relatorios/buscar_propriedades_areas.php?propriedade_id=${propriedade_id}`);

      if (!resp.ok) {
        const txt = await resp.text();
        console.error("Erro HTTP:", txt);
        throw new Error("Erro ao buscar áreas");
      }

      const data = await resp.json();

      if (!data.ok) throw new Error(data.err || "Erro ao carregar áreas");

      selectArea.innerHTML = '<option value="">Todas as áreas</option>';

      if (data.areas.length === 0) {
        selectArea.innerHTML += '<option disabled>Nenhuma área encontrada</option>';
        return;
      }

      data.areas.forEach(a => {
        const opt = document.createElement("option");
        opt.value = a.id;
        opt.textContent = a.nome;
        selectArea.appendChild(opt);
      });

    } catch (err) {
      console.error("❌ Erro áreas:", err);
      alert("Erro ao carregar áreas");
    }
  }

  /* ===============================
  🔹 EVENTOS
  =============================== */

  if (selectProp) {
    selectProp.addEventListener("change", function () {
      carregarAreas(this.value);
    });
  }

  /* ===============================
  🚀 INIT
  =============================== */

  carregarPropriedades();

});

/* ===============================
📄 GERAR PDF
=============================== */

const btnPDF = document.getElementById("form-pdf-relatorio");

if (btnPDF) {

  btnPDF.addEventListener("click", async () => {

    const loading = document.getElementById("pdf-loading");
    const propriedade = document.getElementById("pf-propriedades")?.value || "";
    const dataIni = document.getElementById("pf-ini")?.value || "";
    const dataFim = document.getElementById("pf-fin")?.value || "";

    if (!propriedade) {
      alert("Selecione uma propriedade.");
      return;
    }
    if (!dataIni || !dataFim) {
      alert("Informe a data inicial e a data final.");
      return;
    }
    if (dataIni > dataFim) {
      alert("A data inicial não pode ser maior que a data final.");
      return;
    }

    if (loading) loading.style.display = "flex";

    const formData = new FormData();
    formData.append("propriedade", propriedade);
    formData.append("area", document.getElementById("pf-area")?.value || "");
    formData.append("data_ini", dataIni);
    formData.append("data_fim", dataFim);

    try {

      const resp = await fetch("/funcoes/relatorios/pdf_fitossanitario.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin"
      });

      const contentType = resp.headers.get("content-type") || "";

      if (!resp.ok) {
        const msg = await resp.text();
        throw new Error(msg || "Erro ao gerar PDF");
      }

      if (!contentType.includes("application/pdf")) {
        const msg = await resp.text();
        throw new Error(msg || "Resposta inválida do servidor");
      }

      const blob = await resp.blob();
      const url = URL.createObjectURL(blob);
      window.open(url, "_blank");

    } catch (err) {

      console.error("❌ erro PDF:", err);
      alert(err.message || "Erro ao gerar PDF");

    } finally {

      if (loading) loading.style.display = "none";

    }

  });

}