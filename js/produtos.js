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

window.deleteProduto = function(id) {
    if (!confirm("Deseja realmente excluir este produto?")) return;

    fetch('../funcoes/excluir_produto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id })
    })
    .then(res => res.json())
    .then(d => {
        if (d.ok) {
            // Remove o produto da tela
            document.getElementById('prod-' + id)?.remove();
            // mostra popup de sucesso
            overlay.classList.remove('d-none');
            popupSuccess.classList.remove('d-none');
        } else {
            overlay.classList.remove('d-none');
            popupFailed.classList.remove('d-none');
            const msgBox = popupFailed.querySelector('.popup-text');
            if (msgBox) msgBox.textContent = d.error || "Erro ao excluir produto.";
        }
    })
    .catch(err => {
        overlay.classList.remove('d-none');
        popupFailed.classList.remove('d-none');
        const msgBox = popupFailed.querySelector('.popup-text');
        if (msgBox) msgBox.textContent = "Falha na requisição: " + err;
    });
};
