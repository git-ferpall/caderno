document.addEventListener("DOMContentLoaded", function(){

    const propriedadeSelect = document.getElementById("pf-propriedade");
    const areaSelect = document.getElementById("pf-area");
    const produtoSelect = document.getElementById("pf-produto");


    /* =============================
       CARREGAR FILTROS
    ============================= */

    function carregarFiltros(propriedade_id = ""){

        let url = "../funcoes/relatorios/buscar_filtros_produtividade.php";

        if(propriedade_id){
            url += "?propriedade_id=" + propriedade_id;
        }

        fetch(url)
        .then(r => r.json())
        .then(data => {

            if(!data.ok) return;

            /* PROPRIEDADES */

            if(propriedadeSelect.options.length <= 1){

                propriedadeSelect.innerHTML = '<option value="">Selecione</option>';

                data.propriedades.forEach(p => {

                    let opt = document.createElement("option");

                    opt.value = p.id;
                    opt.textContent = p.nome_razao;

                    propriedadeSelect.appendChild(opt);

                });

            }

            /* AREAS */

            areaSelect.innerHTML = '<option value="">Todas as áreas</option>';

            data.areas.forEach(a => {

                let opt = document.createElement("option");

                opt.value = a.id;
                opt.textContent = a.nome;

                areaSelect.appendChild(opt);

            });


            /* PRODUTOS */

            produtoSelect.innerHTML = '<option value="">Todos os produtos</option>';

            data.produtos.forEach(p => {

                let opt = document.createElement("option");

                opt.value = p.id;
                opt.textContent = p.nome;

                produtoSelect.appendChild(opt);

            });

        });

    }


    /* =============================
       AO TROCAR PROPRIEDADE
    ============================= */

    propriedadeSelect.addEventListener("change", function(){

        const prop_id = this.value;

        areaSelect.innerHTML = '<option value="">Todas as áreas</option>';

        if(!prop_id) return;

        carregarFiltros(prop_id);

    });


    /* =============================
       INICIO
    ============================= */

    carregarFiltros();

});