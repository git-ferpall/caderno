document.addEventListener("DOMContentLoaded", () => {

    // 🟢 Adicionar nova estufa
    document.getElementById("form-save-estufa").addEventListener("click", async () => {
        const nome = document.getElementById("e-nome").value.trim();
        const area = document.getElementById("e-area").value.trim();
        const obs  = document.getElementById("e-obs").value.trim();

        if (!nome) {
            alert("Informe o nome da estufa");
            return;
        }

        const res = await fetch("../funcoes/salvar_estufa.php", {
            method: "POST",
            body: new URLSearchParams({ nome, area_m2: area, obs })
        });
        const data = await res.json();

        if (data.ok) {
            location.reload();
        } else {
            alert("Erro: " + data.err);
        }
    });

    // 🟢 Adicionar nova bancada
    document.querySelectorAll("[id^='form-save-bancada-estufa-']").forEach(btn => {
        btn.addEventListener("click", async e => {
            const id = e.target.id.split("-").pop();
            const nome = document.querySelector(`#item-add-bancada-estufa-${id} #b-nome`).value.trim();
            const cultura = document.querySelector(`#item-add-bancada-estufa-${id} #b-area`).value.trim();
            const obs = document.querySelector(`#item-add-bancada-estufa-${id} #b-obs`).value.trim();

            if (!nome) {
                alert("Informe o nome/número da bancada");
                return;
            }

            const res = await fetch("../funcoes/salvar_bancada.php", {
                method: "POST",
                body: new URLSearchParams({ estufa_id: id, nome, cultura, obs })
            });
            const data = await res.json();

            if (data.ok) {
                location.reload();
            } else {
                alert("Erro: " + data.err);
            }
        });
    });

    // 🧩 Eventos de clique para abrir formulários (defensivo, fertilizante, colheita)
    document.querySelectorAll(".item-bancada-option").forEach(btn => {
        btn.addEventListener("click", e => {
            const btnId = e.currentTarget.id;
            const parts = btnId.split("-");
            const tipo = parts.pop(); // ex: defensivo, fertilizante, colheita
            const nomeBancada = parts[2];
            const idEstufa = parts[4];

            const form = document.querySelector(`#add-e-${idEstufa}-b-${nomeBancada}-${tipo}`);
            if (form) {
                form.classList.toggle("d-none");
            }
        });
    });
});


// 🧩 Selecionar bancada
function selectBancada(nomeBancada, idEstufa) {
    // Fecha todas as bancadas abertas
    document.querySelectorAll(".item-bancada-content").forEach(div => {
        div.classList.add("d-none");
    });

    // Oculta o bloco de estufas
    document.querySelectorAll(".item-estufa-box").forEach(div => {
        div.classList.add("d-none");
    });

    // Mostra a bancada selecionada
    const box = document.getElementById(`item-bancada-${nomeBancada}-content-estufa-${idEstufa}`);
    if (box) box.classList.remove("d-none");
}

// 🧩 Voltar da bancada para a estufa
function voltarEstufa(idEstufa) {
    document.querySelectorAll(".item-bancada-content").forEach(div => div.classList.add("d-none"));

    const box = document.getElementById(`estufa-${idEstufa}-box`);
    if (box) box.classList.remove("d-none");
}

// 🧩 Selecionar estufa
function selectEstufa(idEstufa) {
    document.querySelectorAll(".item-estufa-box").forEach(div => div.classList.add("d-none"));
    const box = document.getElementById(`estufa-${idEstufa}-box`);
    if (box) box.classList.remove("d-none");
}
