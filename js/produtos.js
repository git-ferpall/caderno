// Salvar produto
document.getElementById('form-save-produto').addEventListener('click', function() {
    const form = document.getElementById('add-produto');
    const data = new FormData(form);

    // ajustei o caminho para ../funcoes/
    fetch('../funcoes/salvar_produto.php', { 
        method: 'POST', 
        body: data 
    })
    .then(res => res.json())
    .then(d => {
        console.log("Resposta salvar_produto.php:", d); // 🔎 debug no console
        if (d.ok) {
            alert("Produto salvo com sucesso! ID: " + d.id);
            location.reload();
        } else {
            alert("Erro: " + d.error);
        }
    })
    .catch(err => {
        console.error("Falha na requisição:", err);
        alert("Falha na requisição: " + err);
    });
});

// Remover produto
function removerProduto(id) {
    if (!confirm("Deseja remover este produto?")) return;
    fetch('../funcoes/remover_produto.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(res => res.json())
    .then(d => {
        console.log("Resposta remover_produto.php:", d); // 🔎 debug
        if (d.ok) {
            location.reload();
        } else {
            alert("Erro: " + d.error);
        }
    })
    .catch(err => {
        console.error("Falha na requisição:", err);
        alert("Falha na requisição: " + err);
    });
}

// Editar produto (carrega valores no formulário)
function editProduto(produto) {
    document.getElementById('p-nome').value = produto.nome;
    document.querySelectorAll('input[name="ptipo"]').forEach(r => r.checked = (r.value === produto.tipo));
    document.querySelectorAll('input[name="patr"]').forEach(r => r.checked = (r.value === produto.atributo));

    // coloca id oculto no form
    let hiddenId = document.getElementById('prod-id');
    if (!hiddenId) {
        hiddenId = document.createElement('input');
        hiddenId.type = 'hidden';
        hiddenId.name = 'id';
        hiddenId.id = 'prod-id';
        document.getElementById('add-produto').appendChild(hiddenId);
    }
    hiddenId.value = produto.id;
}
