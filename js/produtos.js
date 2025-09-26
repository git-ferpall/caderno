// =============================
// Produtos - Frontend JS
// =============================

// Botão "Novo Produto" → mostra a caixa do formulário
document.getElementById('produto-add').addEventListener('click', () => {
    const box = document.getElementById('item-add-produto');
    box.classList.remove('d-none');   // garante que aparece
});

// Botão "Cancelar" → fecha formulário sem enviar
document.getElementById('form-cancel-produto').addEventListener('click', () => {
    document.getElementById('item-add-produto').classList.add('d-none');
});

// Botão "Salvar" → submete formulário normalmente (form action = salvar_produto.php)
// O popup de sucesso/erro já é tratado pelo backend + popups.js
document.getElementById('form-save-produto').addEventListener('click', () => {
    const form = document.getElementById('add-produto');

    // validação simples antes de enviar
    const nome = document.getElementById('p-nome').value.trim();
    const tipo = document.querySelector('input[name="ptipo"]:checked')?.value;
    const atr  = document.querySelector('input[name="patr"]:checked')?.value;

    if (!nome || !tipo || !atr) {
        overlay.classList.remove('d-none');
        popupFailed.classList.remove('d-none');
        const msgBox = popupFailed.querySelector('.popup-text');
        if (msgBox) msgBox.textContent = "Preencha todos os campos antes de salvar.";
        return;
    }

    // envia formulário (vai para salvar_produto.php)
    form.submit();
});

// =============================
// Excluir Produto
// =============================

function deleteProduto(id) {
    if (!confirm("Deseja realmente excluir este produto?")) {
        return;
    }

    fetch('../funcoes/excluir_produto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok) {
            // remove da tela
            document.getElementById('prod-' + id).remove();

            overlay.classList.remove('d-none');
            popupSuccess.classList.remove('d-none');
            popupSuccess.querySelector('.popup-title').textContent = "Produto excluído com sucesso!";
        } else {
            overlay.classList.remove('d-none');
            popupFailed.classList.remove('d-none');
            popupFailed.querySelector('.popup-title').textContent = "Erro ao excluir";
            popupFailed.querySelector('.popup-text').textContent = data.error || "Não foi possível excluir o produto.";
        }
    })
    .catch(() => {
        overlay.classList.remove('d-none');
        popupFailed.classList.remove('d-none');
        popupFailed.querySelector('.popup-title').textContent = "Erro inesperado";
        popupFailed.querySelector('.popup-text').textContent = "Falha de comunicação com o servidor.";
    });
}
