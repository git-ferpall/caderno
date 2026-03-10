document.addEventListener("DOMContentLoaded", function(){

    const propriedade = document.getElementById("pf-propriedade");
    const area = document.getElementById("pf-area");
    const produto = document.getElementById("pf-produto");


    /* =========================
       CARREGAR FILTROS
    ========================= */

    function carregarFiltros(prop_id = ""){

        let url = "../funcoes/relatorios/buscar_filtros_produtividade.php";

        if(prop_id){
            url += "?propriedade_id=" + prop_id;
        }

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

            area.innerHTML = '<option value="">Todas as áreas</option>';

            data.areas.forEach(a => {

                let opt = document.createElement("option");

                opt.value = a.id;
                opt.textContent = a.nome;

                area.appendChild(opt);

            });


            /* PRODUTOS */

            produto.innerHTML = '<option value="">Todos os produtos</option>';

            data.produtos.forEach(p => {

                let opt = document.createElement("option");

                opt.value = p.id;
                opt.textContent = p.nome;

                produto.appendChild(opt);

            });

        });

    }


    /* =========================
       TROCA PROPRIEDADE
    ========================= */

    $('#pf-propriedade').on('change', function(){

        const prop_id = $(this).val();

        area.innerHTML = '<option value="">Todas as áreas</option>';

        if(!prop_id) return;

        carregarFiltros(prop_id);

    });


    /* =========================
       INICIO
    ========================= */

    carregarFiltros();

});