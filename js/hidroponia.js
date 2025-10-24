document.addEventListener("DOMContentLoaded", () => {
    carregarHidroponia();

    async function carregarHidroponia() {
        const container = document.getElementById("hidroponia-container");
        container.innerHTML = '<div class="loading">Carregando...</div>';

        const res = await fetch("../funcoes/hidroponia/carregar_hidroponia.php");
        const data = await res.json();

        if (!data.ok) {
            container.innerHTML = `<div class="erro">${data.err}</div>`;
            return;
        }

        let html = `
            <button class="btn-add" onclick="abrirFormEstufa()">+ Nova Estufa</button>
        `;

        data.areas.forEach(area => {
            area.estufas.forEach(estufa => {
                html += `
                <div class="item item-estufa">
                    <h4>${estufa.nome}</h4>
                    <p><strong>Área:</strong> ${estufa.area_m2} m²</p>
                    <p>${estufa.obs || ''}</p>

                    <button onclick="abrirFormBancada(${estufa.id})">+ Adicionar Bancada</button>
                    <div class="bancadas">
                        ${estufa.bancadas.map(b => `
                            <div class="bancada">
                                <span>${b.nome}</span>
                                <small>${b.cultura || ''}</small>
                            </div>
                        `).join('')}
                    </div>
                </div>`;
            });
        });

        container.innerHTML = html;
    }

    window.abrirFormEstufa = function() {
        const nome = prompt("Nome da Estufa:");
        if (!nome) return;
        fetch("../funcoes/hidroponia/salvar_estufa.php", {
            method: "POST",
            body: new URLSearchParams({ nome })
        }).then(r => r.json()).then(d => {
            if (d.ok) carregarHidroponia();
            else alert(d.err);
        });
    };

    window.abrirFormBancada = function(estufa_id) {
        const nome = prompt("Nome da Bancada:");
        if (!nome) return;
        fetch("../funcoes/hidroponia/salvar_bancada.php", {
            method: "POST",
            body: new URLSearchParams({ estufa_id, nome })
        }).then(r => r.json()).then(d => {
            if (d.ok) carregarHidroponia();
            else alert(d.err);
        });
    };
});
