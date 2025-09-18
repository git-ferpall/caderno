function editItem(maquina) {
    // Simula o clique no botão "Nova Máquina" (que já abre o formulário)
    document.getElementById('maquina-add').click();

    // Preenche os campos
    document.getElementById('m-id').value = maquina.id;
    document.getElementById('m-nome').value = maquina.nome;
    document.getElementById('m-marca').value = maquina.marca;

    if (maquina.tipo) {
        const radio = document.querySelector(`input[name="mtipo"][value="${maquina.tipo}"]`);
        if (radio) {
            radio.checked = true;
        }
    } else {
        document.querySelector('input[name="mtipo"][value="1"]').checked = true;
    }
}


function cancelarEdicao() {
    document.getElementById('m-id').value = '';
    document.getElementById('m-nome').value = '';
    document.getElementById('m-marca').value = '';
    document.querySelector('input[name="mtipo"][value="1"]').checked = true;

    document.getElementById('item-add-maquina').style.display = 'none';
}
