document.addEventListener("DOMContentLoaded", function () {

    const pfPropriedade = document.getElementById("pf-propriedade");
    const pfArea = document.getElementById("pf-area");

    /* ===============================
    CARREGAR PROPRIEDADES
    =============================== */

    function carregarPropriedades() {

        fetch("../funcoes/relatorios/buscar_filtros_produtividade.php")
        .then(res => res.json())
        .then(data => {

            if(!data.ok) return;

            pfPropriedade.innerHTML =
                '<option value="">Selecione</option>';

            data.propriedades.forEach(prop => {

                const opt = document.createElement("option");

                opt.value = prop.id;
                opt.textContent = prop.nome_razao;

                pfPropriedade.appendChild(opt);

            });

        });

    }

    /* ===============================
    CARREGAR AREAS
    =============================== */

    function carregarAreas(propriedadeId) {

        pfArea.innerHTML =
            '<option value="">Carregando...</option>';

        fetch("../funcoes/relatorios/buscar_filtros_produtividade.php?propriedade_id="+propriedadeId)

        .then(res => res.json())

        .then(data => {

            pfArea.innerHTML =
                '<option value="">Todas as áreas</option>';

            if(!data.areas) return;

            data.areas.forEach(area => {

                const opt = document.createElement("option");

                opt.value = area.id;
                opt.textContent = area.nome;

                pfArea.appendChild(opt);

            });

        });

    }

    /* ===============================
    AO TROCAR PROPRIEDADE
    =============================== */

    pfPropriedade.addEventListener("change", function(){

        const propId = this.value;

        /* limpa áreas antigas */

        pfArea.innerHTML =
            '<option value="">Todas as áreas</option>';

        if(!propId) return;

        carregarAreas(propId);

    });


    carregarPropriedades();

});