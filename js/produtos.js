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

let produtoParaExcluir = null;

function deleteProduto(id) {
    produtoParaExcluir = id;
    overlay.classList.remove('d-none');
    document.getElementById('popup-delete').classList.remove('d-none');
}

// quando clicar em confirmar exclusão
document.getElementById('confirm-delete').addEventListener('click', function() {
    if (!produtoParaExcluir) return;

    fetch('../funcoes/excluir_produto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(produtoParaExcluir)
    })
    .then(res => res.json())
    .then(data => {
        closePopup();
        if (data.ok) {
            document.getElementById('prod-' + produtoParaExcluir)?.remove();
            showPopupSuccess("Produto excluído com sucesso!");
        } else {
            showPopupFailed("Erro ao excluir", data.error || "Não foi possível excluir o produto.");
        }
    })
    .catch(() => {
        closePopup();
        showPopupFailed("Erro inesperado", "Falha de comunicação com o servidor.");
    });

    produtoParaExcluir = null;
});

