const form = document.getElementById('add-maquina');
const inputId   = document.getElementById('m-id'); // precisa criar hidden
const inputNome = document.getElementById('m-nome');
const inputMarca= document.getElementById('m-marca');

// Novo
document.getElementById('maquina-add').addEventListener('click', () => {
    limparFormulario();
    document.getElementById('item-add-maquina').classList.remove('d-none');
});

// Cancelar
document.getElementById('form-cancel-maquina').addEventListener('click', () => {
    document.getElementById('item-add-maquina').classList.add('d-none');
    limparFormulario();
});

// Salvar
document.getElementById('form-save-maquina').addEventListener('click', () => {
    const nome  = inputNome.value.trim();
    const marca = inputMarca.value.trim();
    const tipo  = document.querySelector('input[name="mtipo"]:checked')?.value;

    if (!nome || !marca || !tipo) {
        showPopupFailed("Preencha todos os campos.");
        return;
    }

    const formData = new FormData(form);

    fetch("../funcoes/salvar_maquina.php", { method:"POST", body:formData })
    .then(r=>r.json())
    .then(data=>{
        if(data.ok){
            location.reload();
        }else{
            showPopupFailed(data.error || "Erro ao salvar máquina.");
        }
    })
    .catch(err=>{
        showPopupFailed("Falha na comunicação: "+err);
    });
});

function editItem(btn){
    const maq = JSON.parse(btn.getAttribute('data-maquina'));
    document.getElementById('maquina-add').click();

    inputId.value   = maq.id;
    inputNome.value = maq.nome;
    inputMarca.value= maq.marca;

    if(maq.tipo === 'motorizado') document.querySelector('input[name="mtipo"][value="1"]').checked = true;
    if(maq.tipo === 'acoplado')   document.querySelector('input[name="mtipo"][value="2"]').checked = true;
    if(maq.tipo === 'manual')     document.querySelector('input[name="mtipo"][value="3"]').checked = true;

    document.querySelector('#form-save-maquina .main-btn-text').textContent = "Atualizar";
}

function limparFormulario(){
    inputId.value = '';
    inputNome.value = '';
    inputMarca.value = '';
    document.querySelector('input[name="mtipo"][value="1"]').checked = true;
    document.querySelector('#form-save-maquina .main-btn-text').textContent = "Salvar";
}
