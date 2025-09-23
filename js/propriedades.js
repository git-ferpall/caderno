document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("prop-form");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(form);

    try {
      const res = await fetch("../funcoes/salvar_propriedade.php", {
        method: "POST",
        body: formData,
        credentials: "include" // importante: envia cookie AUTH_TOKEN
      });

      const data = await res.json();

      if (data.ok) {
        alert("Propriedade salva com sucesso!");
        window.location.href = "propriedade.php"; // recarrega a página
      } else {
        alert("Erro ao salvar: " + (data.msg || data.err));
      }
    } catch (err) {
      alert("Falha de conexão: " + err.message);
    }
  });
});
