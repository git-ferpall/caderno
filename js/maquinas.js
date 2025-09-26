// =============================
// Máquinas - Frontend JS
// =============================

// Referências do formulário
const formMaq      = document.getElementById('add-maquina');
const inputIdMaq   = document.getElementById('m-id');   // hidden
const inputNomeMaq = document.getElementById('m-nome');
const inputMarcaMaq= document.getElementById('m-marca');

// Botão "Nova máquina"
document.getElementById('maquina-add').addEventListener('click', () => {
    limparFormulario();
    document.getElementById('item-add-maquina').classList.remove('d-none');
});

// Botão "Cancelar"
document.getElementById('form-cancel-maquina').addEventListener('click', () => {
    document.getElementById('item-add-maquina').classList.add('d-none');
    limparFormulario();
});

// Botão "Salvar"
document.getElementById('form-save-maquina').addEventListener('click', () => {
    const nome  = inputNomeMaq.value.trim();
    const marca = inputMarcaMaq.value.trim();
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
            location.reload(); // ✅ Atualiza lista
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
// Editar Máquina
// =============================
function editItem(btn) {
    const maq = JSON.parse(btn.getAttribute('data-maquina'));
    document.getElementById('maquina-add').click(); // abre box

    inputIdMaq.value    = maq.id;
    inputNomeMaq.value  = maq.nome;
    inputMarcaMaq.value = maq.marca;

    if(maq.tipo === 'motorizado') document.querySelector('input[name="mtipo"][value="1"]').checked = true;
    if(maq.tipo === 'acoplado')   document.querySelector('input[name="mtipo"][value="2"]').checked = true;
    if(maq.tipo === 'manual')     document.querySelector('input[name="mtipo"][value="3"]').checked = true;

    document.querySelector('#form-save-maquina .main-btn-text').textContent = "Atualizar";
}

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
// Utilitários
// =============================
function limparFormulario(){
    inputIdMaq.value = '';
    inputNomeMaq.value = '';
    inputMarcaMaq.value = '';
    document.querySelector('input[name="mtipo"][value="1"]').checked = true;
    document.querySelector('#form-save-maquina .main-btn-text').textContent = "Salvar";
}
