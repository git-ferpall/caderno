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
            location.reload(); // atualiza a listagem
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

});
