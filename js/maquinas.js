// =============================
// Máquinas - Frontend JS
// =============================

const formMaq   = document.getElementById('add-maquina');
const inputIdM  = document.getElementById('m-id');   // hidden
const inputNomeM= document.getElementById('m-nome');
const inputMarca= document.getElementById('m-marca');

// Botão "Nova Máquina" → mostra o formulário limpo
document.getElementById('maquina-add').addEventListener('click', () => {
    limparFormularioMaq();
    document.getElementById('item-add-maquina').classList.remove('d-none');
});

// Botão "Cancelar" → fecha formulário
document.getElementById('form-cancel-maquina').addEventListener('click', () => {
    document.getElementById('item-add-maquina').classList.add('d-none');
    limparFormularioMaq();
});

// Botão "Salvar"
document.getElementById('form-save-maquina').addEventListener('click', () => {
    const nome  = inputNomeM.value.trim();
    const marca = inputMarca.value.trim();
    const tipo  = document.querySelector('input[name="mtipo"]:checked')?.value;

    if (!nome || !marca || !tipo) {
        overlay.classList.remove('d-none');
        popupFailed.classList.remove('d-none');
        const msgBox = popupFailed.querySelector('.popup-text');
        if (msgBox) msgBox.textContent = "Preencha todos os campos antes de salvar.";
        return;
    }

    const formData = new FormData(formMaq);

    fetch(formMaq.action, {
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
            if (msgBox) msgBox.textContent = data.error || "Erro ao salvar a máquina.";
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
// Excluir Máquina
// =============================
let maquinaParaExcluir = null;

function deleteMaquina(id) {
    maquinaParaExcluir = id;
    overlay.classList.remove('d-none');
    document.getElementById('popup-delete').classList.remove('d-none');
}

document.getElementById('confirm-delete').addEventListener('click', function() {
    if (!maquinaParaExcluir) return;

    fetch('../funcoes/excluir_maquina.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(maquinaParaExcluir)
    })
    .then(res => res.json())
    .then(data => {
        closePopup();

        if (data.ok) {
            location.reload();
        } else {
            showPopupFailed("Erro ao excluir", data.error || "Não foi possível excluir a máquina.");
        }
    })
    .catch(() => {
        closePopup();
        showPopupFailed("Erro inesperado", "Falha de comunicação com o servidor.");
    });

    maquinaParaExcluir = null;
});

// =============================
// Editar Máquina
// =============================
function editItem(btn) {
    const maq = JSON.parse(btn.getAttribute('data-maquina'));

    // Simula clique no botão "Nova Máquina"
    document.getElementById('maquina-add').click();

    // Preenche o formulário
    inputIdM.value   = maq.id;
    inputNomeM.value = maq.nome;
    inputMarca.value = maq.marca;

    if (maq.tipo === 'motorizado') document.querySelector('input[name="mtipo"][value="1"]').checked = true;
    if (maq.tipo === 'acoplado')   document.querySelector('input[name="mtipo"][value="2"]').checked = true;
    if (maq.tipo === 'manual')     document.querySelector('input[name="mtipo"][value="3"]').checked = true;

    // muda texto do botão para "Atualizar"
    document.querySelector('#form-save-maquina .main-btn-text').textContent = "Atualizar";
}

// =============================
// Utilitários
// =============================
function limparFormularioMaq() {
    inputIdM.value = '';
    inputNomeM.value = '';
    inputMarca.value = '';
    document.querySelector('input[name="mtipo"][value="1"]').checked = true;
    document.getElementById('form-save-maquina').querySelector('.main-btn-text').textContent = "Salvar";
}
