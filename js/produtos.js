// =============================
// Produtos - Frontend JS
// =============================

const form = document.getElementById('add-produto');
const inputId   = document.getElementById('p-id');
const inputNome = document.getElementById('p-nome');

// Botão "Novo Produto" → mostra o formulário limpo
document.getElementById('produto-add').addEventListener('click', () => {
    limparFormulario();
    document.getElementById('item-add-produto').classList.remove('d-none');
});

// Botão "Cancelar" → fecha formulário
document.getElementById('form-cancel-produto').addEventListener('click', () => {
    document.getElementById('item-add-produto').classList.add('d-none');
    limparFormulario();
});

// Botão "Salvar"
document.getElementById('form-save-produto').addEventListener('click', () => {
    const nome = inputNome.value.trim();
    const tipo = document.querySelector('input[name="ptipo"]:checked')?.value;
    const atr  = document.querySelector('input[name="patr"]:checked')?.value;

    if (!nome || !tipo || !atr) {
        overlay.classList.remove('d-none');
        popupFailed.classList.remove('d-none');
        const msgBox = popupFailed.querySelector('.popup-text');
        if (msgBox) msgBox.textContent = "Preencha todos os campos antes de salvar.";
        return;
    }

    const formData = new FormData(form);

    fetch(form.action, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok) {
            // Recarrega página para atualizar lista
            location.reload();
        } else {
            overlay.classList.remove('d-none');
            popupFailed.classList.remove('d-none');
            const msgBox = popupFailed.querySelector('.popup-text');
            if (msgBox) msgBox.textContent = data.error || "Erro ao salvar o produto.";
        }
    })
    .catch(err => {
        overlay.classList.remove('d-none');
        popupFailed.classList.remove('d-none');
        const msgBox = popupFailed.querySelector('.popup-text');
        if (msgBox) msgBox.textContent = "Falha na comunicação: " + err;
    });
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
            location.reload();
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

// =============================
// Editar Produto
// =============================
function editItem(btn) {
    const produto = JSON.parse(btn.getAttribute('data-produto'));

    // preenche o formulário
    inputId.value   = produto.id;
    inputNome.value = produto.nome;

    // tipo
    if (produto.tipo === 'convencional') document.querySelector('input[name="ptipo"][value="1"]').checked = true;
    if (produto.tipo === 'organico')     document.querySelector('input[name="ptipo"][value="2"]').checked = true;
    if (produto.tipo === 'integrado')    document.querySelector('input[name="ptipo"][value="3"]').checked = true;

    // atributo
    if (produto.atributo === 'hidro')      document.querySelector('input[name="patr"][value="hidro"]').checked = true;
    if (produto.atributo === 'semi-hidro') document.querySelector('input[name="patr"][value="semi-hidro"]').checked = true;
    if (produto.atributo === 'solo')       document.querySelector('input[name="patr"][value="solo"]').checked = true;

    // abre o box
    document.getElementById('item-add-produto').classList.remove('d-none');

    // opcional: mudar texto do botão
    document.getElementById('form-save-produto').querySelector('.main-btn-text').textContent = "Atualizar";
}

// =============================
// Utilitários
// =============================
function limparFormulario() {
    inputId.value = '';
    inputNome.value = '';
    document.querySelector('input[name="ptipo"][value="1"]').checked = true;
    document.querySelector('input[name="patr"][value="hidro"]').checked = true;
    document.getElementById('form-save-produto').querySelector('.main-btn-text').textContent = "Salvar";
}
