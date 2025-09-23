function carregarPropriedade(id) {
    const token = localStorage.getItem("jwt_token");

    fetch(`/funcoes/get_propriedade.php?id=${id}`, {
        method: "GET",
        headers: {
            "Authorization": `Bearer ${token}`
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            // Fecha popup
            closePopup();

            // Preenche os campos do formulário
            document.getElementById("pf-razao").value = data.data.nome_razao || "";
            document.getElementById("pf-tipo").value = data.data.tipo_doc || "";
            document.getElementById("pf-cnpj").value = (data.data.tipo_doc === "cnpj" ? data.data.cpf_cnpj : "");
            document.getElementById("pf-cpf").value = (data.data.tipo_doc === "cpf" ? data.data.cpf_cnpj : "");
            document.getElementById("pf-email-com").value = data.data.email || "";
            document.getElementById("pf-ender-rua").value = data.data.endereco_rua || "";
            document.getElementById("pf-ender-num").value = data.data.endereco_numero || "";
            document.getElementById("pf-ender-uf").value = data.data.endereco_uf || "";
            document.getElementById("pf-ender-cid").value = data.data.endereco_cidade || "";
            document.getElementById("pf-num1-com").value = data.data.telefone1 || "";
            document.getElementById("pf-num2-com").value = data.data.telefone2 || "";
        } else {
            alert("Erro ao carregar propriedade!");
        }
    })
    .catch(() => alert("Erro ao carregar propriedade!"));
}
