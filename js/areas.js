// =============================
// Áreas - Frontend JS
// =============================

const formArea   = document.getElementById('add-area');
const inputIdA   = document.getElementById('a-id'); // hidden input
const inputNomeA = document.getElementById('a-nome');

// Botão "Nova Área"
document.getElementById('area-add').addEventListener('click', () => {
    limparFormularioArea();
    document.getElementById('item-add-area').classList.remove('d-none');
});

// Cancelar
document.getElementById('form-cancel-area').addEventListener('click', () => {
    document.getElementById('item-add-area').classList.add('d-none');
    limparFormularioArea();
});

// Salvar
document.getElementById('form-save-area').addEventListener('click', () => {
    const nome = inputNomeA.value.trim();
    const tipo = document.querySelector('input[name="atipo"]:checked')?.value;

    if (!nome || !tipo) {
        overlay.classList.remove('d-none');
        popupFailed.classList.remove('d-none');
        popupFailed.querySelector('.popup-text').textContent = "Preencha todos os campos.";
        return;
    }

    const formData = new FormData(formArea);

    fetch(formArea.action, { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                location.reload();
            } else {
                overlay.classList.remove('d-none');
                popupFailed.classList.remove('d-none');
                popupFailed.querySelector('.popup-text').textContent = data.error || "Erro ao salvar área.";
            }
        })
        .catch(err => {
            overlay.classList.remove('d-none');
            popupFailed.classList.remove('d-none');
            popupFailed.querySelector('.popup-text').textContent = "Falha: " + err;
        });
});

// Excluir
let areaParaExcluir = null;
function deleteArea(id) {
    areaParaExcluir = id;
    overlay.classList.remove('d-none');
    document.getElementById('popup-delete').classList.remove('d-none');
}
document.getElementById('confirm-delete').addEventListener('click', () => {
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
                showPopupFailed("Erro ao excluir", data.error || "Não foi possível excluir.");
            }
        })
        .catch(() => {
            closePopup();
            showPopupFailed("Erro inesperado", "Falha de comunicação.");
        });

    areaParaExcluir = null;
});

// Editar
function editItem(btn) {
    const area = JSON.parse(btn.getAttribute('data-area'));
    document.getElementById('area-add').click();

    inputIdA.value   = area.id;
    inputNomeA.value = area.nome;

    if (area.tipo === 'estufa') document.querySelector('input[name="atipo"][value="1"]').checked = true;
    if (area.tipo === 'solo')   document.querySelector('input[name="atipo"][value="2"]').checked = true;
    if (area.tipo === 'outro')  document.querySelector('input[name="atipo"][value="3"]').checked = true;

    document.querySelector('#form-save-area .main-btn-text').textContent = "Atualizar";
}

// Utilitários
function limparFormularioArea() {
    inputIdA.value = '';
    inputNomeA.value = '';
    document.querySelector('input[name="atipo"][value="1"]').checked = true;
    document.getElementById('form-save-area').querySelector('.main-btn-text').textContent = "Salvar";
}
