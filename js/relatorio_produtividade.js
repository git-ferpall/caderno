document.addEventListener("DOMContentLoaded", function(){

    const propriedade = document.getElementById("pf-propriedade");
    const area = document.getElementById("pf-area");
    const produto = document.getElementById("pf-produto");
    const btn = document.getElementById("form-pdf-relatorio");

    area.disabled = true;
    produto.disabled = true;

    function carregarFiltros(prop_id = ""){

        let url = "../funcoes/relatorios/buscar_filtros_produtividade.php";

        const params = new URLSearchParams();

        if(prop_id){
            params.append("propriedade_id", prop_id);
        }

        url += "?" + params.toString();

        fetch(url)
        .then(res => res.json())
        .then(data => {

            if(!data.ok) return;

            /* PROPRIEDADES */

            if(propriedade.options.length <= 1){

                propriedade.innerHTML = '<option value="">Selecione</option>';

                data.propriedades.forEach(p => {

                    let opt = document.createElement("option");

                    opt.value = p.id;
                    opt.textContent = p.nome_razao;

                    propriedade.appendChild(opt);

                });

                $('#pf-propriedade').trigger('change.select2');

            }

            /* AREAS */

            area.innerHTML = '<option value="" disabled selected>Selecione a área</option>';

            data.areas.forEach(a => {

                let opt = document.createElement("option");

                opt.value = a.id;
                opt.textContent = a.nome;

                area.appendChild(opt);

            });

            area.disabled = false;

            /* PRODUTOS */

            produto.innerHTML = '<option value="" disabled selected>Selecione o produto</option>';

            data.produtos.forEach(p => {

                let opt = document.createElement("option");

                opt.value = p.id;
                opt.textContent = p.nome;

                produto.appendChild(opt);

            });

            produto.disabled = false;

        })
        .catch(err => {
            console.error("Erro ao carregar filtros:", err);
        });

    }

    $('#pf-propriedade').on('change', function(){

        const prop_id = $(this).val();

        area.innerHTML = '<option value="" disabled selected>Selecione a área</option>';
        produto.innerHTML = '<option value="" disabled selected>Selecione o produto</option>';

        area.disabled = true;
        produto.disabled = true;

        if(!prop_id) return;

        carregarFiltros(prop_id);

    });

    carregarFiltros();

    /* ==============================
       VALIDAÇÃO DO FORMULÁRIO
    ============================== */

    btn.addEventListener("click", function(){

        const prop = propriedade.value;
        const ar = area.value;
        const prod = produto.value;
        const data_ini = document.querySelector("input[name='data_ini']").value;
        const data_fim = document.querySelector("input[name='data_fim']").value;

        if(!prop || !ar || !prod || !data_ini || !data_fim){

            alert("Preencha todos os campos para gerar o relatório.");

            return;

        }

        document.getElementById("rel-form").submit();

    });

});