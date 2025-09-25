function salvarProduto(formId) {
    const form = document.getElementById(formId);
    const data = new FormData(form);

    fetch('/funcoes/salvar_produto.php', { method: 'POST', body: data })
      .then(r => r.json())
      .then(d => {
        console.log("Resposta salvar_produto.php:", d); // debug
        if (d.ok) {
            alert("Produto salvo com sucesso! ID: " + d.id);
            location.reload();
        } else {
            alert("Erro: " + d.error);
        }
    });
}

function removerProduto(id) {
    if (!confirm("Deseja remover este produto?")) return;
    fetch('/funcoes/remover_produto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id='+id
    })
    .then(r=>r.json())
    .then(d=>{
        if (d.ok) location.reload();
        else alert(d.error);
    });
}
function salvarProduto(formId) {
    const form = document.getElementById(formId);
    const data = new FormData(form);

    fetch('/funcoes/salvar_produto.php', { method: 'POST', body: data })
      .then(r => r.json())
      .then(d => {
          if (d.ok) location.reload();
          else alert(d.error);
      });
}

function removerProduto(id) {
    if (!confirm("Deseja remover este produto?")) return;
    fetch('../funcoes/remover_produto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id='+id
    })
    .then(r=>r.json())
    .then(d=>{
        if (d.ok) location.reload();
        else alert(d.error);
    });
}
