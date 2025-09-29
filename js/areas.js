// =============================
// Áreas - Frontend JS
// =============================

const formArea   = document.getElementById('add-area');
const inputIdA   = document.getElementById('a-id');   // precisa de hidden <input>
const inputNomeA = document.getElementById('a-nome');

// Botão "Nova Área" → mostra o formulário limpo
document.getElementById('area-add').addEventListener('click', () => {
    limparFormularioArea();
    document.getElementById('item-add-area').classList.remove('d-none');
});

// Botão "Cancelar" → fecha formulário
document.getElementById('form-cancel-area').addEventListener('click', () => {
    document.getElementById('item-add-area').classList.add('d-none');
    limparFormularioArea();
});

// Botão "Salvar"
document.getElementById('form-save-area').addEventListener('click', () => {
    const nome = inputNomeA.value.trim();
    const tipo = document.querySelector('input[name="atipo"]:checked')?.value;

    if (!nome || !tipo) {
        overlay.classList.remove('d-none');
        popupFailed.classList.remove('d-none');
        const msgBox = popupFailed.querySelector('.popup-text');
        if (msgBox) msgBox.textContent = "Preencha todos os campos antes de salvar.";
        return;
    }

    const formData = new FormData(formArea);

    fetch(formArea.action, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok) {
            location.reload(); // ✅ Recarrega página após salvar
        } else {
            overlay.classList.remove('d-none');
            popupFailed.classList.remove('d-none');
            const msgBox = popupFailed.querySelector('.popup-text');
            if (msgBox) msgBox.textContent = data.error || "Erro ao salvar a área.";
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
// Excluir Área
// =============================
let areaParaExcluir = null;

function deleteArea(id) {
    areaParaExcluir = id;
    overlay.classList.remove('d-none');
    document.getElementById('popup-delete').classList.remove('d-none');
}

document.getElementById('confirm-delete').addEventListener('click', function() {
    if (!areaParaExcluir) return;

    fetch('../funcoes/excluir_area.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(areaParaExcluir)
    })
    .then(res => res.json())
    .then(data => {
        closePopup();
        if (data.ok) {
            location.reload();
        } else {
            showPopupFailed("Erro ao excluir", data.error || "Não foi possível excluir a área.");
        }
    })
    .catch(() => {
        closePopup();
        showPopupFailed("Erro inesperado", "Falha de comunicação com o servidor.");
    });

    areaParaExcluir = null;
});

// =============================
// Editar Área
// =============================
function editItem(btn) {
    const area = JSON.parse(btn.getAttribute('data-area'));

    // Simula clique no botão "Nova Área"
    document.getElementById('area-add').click();

    // Preenche o formulário
    inputIdA.value   = area.id;
    inputNomeA.value = area.nome;

    if (area.tipo === 'estufa') document.querySelector('input[name="atipo"][value="1"]').checked = true;
    if (area.tipo === 'solo')   document.querySelector('input[name="atipo"][value="2"]').checked = true;
    if (area.tipo === 'outro')  document.querySelector('input[name="atipo"][value="3"]').checked = true;

    // muda texto do botão para "Atualizar"
    document.querySelector('#form-save-area .main-btn-text').textContent = "Atualizar";
}

// =============================
// Utilitários
// =============================
function limparFormularioArea() {
    inputIdA.value = '';
    inputNomeA.value = '';
    document.querySelector('input[name="atipo"][value="1"]').checked = true;
    document.getElementById('form-save-area').querySelector('.main-btn-text').textContent = "Salvar";
}
