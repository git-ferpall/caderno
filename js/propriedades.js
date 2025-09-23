function selectPropriedade(id) {
    fetch('/funcoes/get_propriedade.php?id=' + id, {
        headers: {
            "Authorization": "Bearer " + localStorage.getItem("jwt") // ajusta se guardar o token em outro lugar
        }
    })
    .then(r => r.json())
    .then(dados => {
        if (dados.ok) {
            const prop = dados.propriedade;

            // Preenche formulário
            document.getElementById('pf-razao').value = prop.nome_razao || '';
            document.getElementById('pf-tipo').value = prop.tipo_doc || '';
            document.getElementById('pf-cnpj').value = prop.cpf_cnpj || '';
            document.getElementById('pf-cpf').value = prop.cpf_cnpj || '';
            document.getElementById('pf-email-com').value = prop.email || '';
            document.getElementById('pf-ender-rua').value = prop.endereco_rua || '';
            document.getElementById('pf-ender-num').value = prop.endereco_numero || '';
            document.getElementById('pf-ender-uf').value = prop.endereco_uf || '';
            document.getElementById('pf-ender-cid').value = prop.endereco_cidade || '';
            document.getElementById('pf-num1-com').value = prop.telefone1 || '';
            document.getElementById('pf-num2-com').value = prop.telefone2 || '';

            // Fecha popup
            if (typeof closePopup === "function") {
                closePopup();
            }
        } else {
            alert("Erro ao carregar propriedade!");
        }
    })
    .catch(err => {
        console.error("Erro na requisição:", err);
        alert("Falha ao buscar a propriedade.");
    });
}
