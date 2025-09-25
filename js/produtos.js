// Referências aos popups
const overlay = document.getElementById('popup-overlay');
const popupSuccess = document.getElementById('popup-success');
const popupFailed = document.getElementById('popup-failed');

// Botão "Novo Produto" → abre o formulário
document.getElementById('produto-add').addEventListener('click', () => {
    document.getElementById('item-add-produto').classList.toggle('d-none');
});

// Botão "Cancelar" → fecha formulário
document.getElementById('form-cancel-produto').addEventListener('click', () => {
    document.getElementById('item-add-produto').classList.add('d-none');
});

// Botão "Salvar" → envia para backend
document.getElementById('form-save-produto').addEventListener('click', () => {
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

    fetch('../funcoes/salvar_produto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            pnome: nome,
            ptipo: tipo,
            patr: atr
        })
    })
    .then(res => res.json())
    .then(d => {
        if (d.ok) {
            // ✅ Sucesso → mostra popup verde
            overlay.classList.remove('d-none');
            popupSuccess.classList.remove('d-none');

            document.getElementById('btn-ok').addEventListener('click', function () {
                location.reload();
            }, { once: true });
        } else {
            // ❌ Erro vindo do backend
            overlay.classList.remove('d-none');
            popupFailed.classList.remove('d-none');

            const msgBox = popupFailed.querySelector('.popup-text');
            if (msgBox) msgBox.textContent = d.error || "Não foi possível salvar o produto.";
        }
    })
    .catch(err => {
        // ❌ Erro de rede ou inesperado
        overlay.classList.remove('d-none');
        popupFailed.classList.remove('d-none');

        const msgBox = popupFailed.querySelector('.popup-text');
        if (msgBox) msgBox.textContent = "Falha na requisição: " + err;
    });
});
