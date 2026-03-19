document.addEventListener("DOMContentLoaded", () => {

  const btn = document.getElementById("form-pdf-relatorio");

  if (!btn) return;

  btn.addEventListener("click", () => {

    const form = document.getElementById("rel-form");

    const props = $("#pf-propriedades").val();
    const area  = document.getElementById("pf-area").value;
    const data_ini = document.getElementById("pf-ini").value;
    const data_fim = document.getElementById("pf-fin").value;

    if (!props || props.length === 0) {
      showPopup("failed", "Selecione pelo menos uma propriedade");
      return;
    }

    if (!data_ini || !data_fim) {
      showPopup("failed", "Informe o período");
      return;
    }

    // loading
    document.getElementById("pdf-loading").style.display = "flex";

    const params = new URLSearchParams();

    props.forEach(p => params.append("propriedade[]", p));
    if (area) params.append("area", area);

    params.append("data_ini", data_ini);
    params.append("data_fim", data_fim);

    // abre PDF
    const url = "../relatorios/pdf_fitossanitario.php?" + params.toString();

    window.open(url, "_blank");

    setTimeout(() => {
      document.getElementById("pdf-loading").style.display = "none";
    }, 1500);

  });

});