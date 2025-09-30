document.addEventListener("DOMContentLoaded", () => {
  // Carregar áreas
  fetch("../funcoes/buscar_areas.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("area");
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = `${item.nome} (${item.tipo})`;
        sel.appendChild(opt);
      });
    });

  // Carregar produtos
  fetch("../funcoes/buscar_produtos.php")
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById("produto");
      data.forEach(item => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome;
        sel.appendChild(opt);
      });
    });

  // Envio do formulário
  document.getElementById("form-colheita").addEventListener("submit", e => {
    e.preventDefault();

    const formData = new FormData(e.target);

    fetch("../funcoes/salvar_colheita.php", {
      method: "POST",
      body: formData
    })
      .then(r => r.json())
      .then(resp => {
        if (resp.ok) {
          alert(resp.msg);
          e.target.reset();
        } else {
          alert("Erro: " + resp.erro);
        }
      })
      .catch(err => alert("Falha: " + err));
  });
});
