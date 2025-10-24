/**
 * HIDROPONIA.JS
 * Controle de cadastro e exibição de estufas e bancadas
 * Sistema Caderno de Campo - Frutag
 */

document.addEventListener("DOMContentLoaded", () => {

    // 🟢 Adicionar nova estufa
    const btnAddEstufa = document.getElementById("form-save-estufa");
    if (btnAddEstufa) {
        btnAddEstufa.addEventListener("click", async () => {
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
    }

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

/* ============================================================
   🧩 Funções de controle visual de estufas e bancadas
   ============================================================ */

/**
 * Abre ou fecha a estufa selecionada
 * - Fecha todas as outras
 * - Alterna "Selecionar" ↔ "Fechar"
 * - Esconde o bloco "+ Nova Estufa"
 */
function selectEstufa(idEstufa) {
    const box = document.getElementById(`estufa-${idEstufa}-box`);
    const btn = document.getElementById(`edit-estufa-${idEstufa}`);
    const novaEstufa = document.getElementById("item-add-estufa");

    if (!box || !btn) return;

    const isOpen = !box.classList.contains("d-none"); // já está aberta?

    // Fecha todas as estufas abertas
    document.querySelectorAll(".item-estufa-box").forEach(div => div.classList.add("d-none"));

    // Reseta todos os botões para "Selecionar"
    document.querySelectorAll(".edit-btn").forEach(b => {
        b.textContent = "Selecionar";
        b.classList.remove("fechar");
    });

    if (isOpen) {
        // Se já estava aberta → fecha
        box.classList.add("d-none");
        btn.textContent = "Selecionar";
        btn.classList.remove("fechar");
        if (novaEstufa) novaEstufa.classList.remove("d-none");
    } else {
        // Se estava fechada → abre
        box.classList.remove("d-none");
        btn.textContent = "Fechar";
        btn.classList.add("fechar");
        if (novaEstufa) novaEstufa.classList.add("d-none");
    }
}

/**
 * Mostra o conteúdo da bancada selecionada
 * - Fecha todas as bancadas abertas
 * - Fecha todas as estufas
 * - Esconde "+ Nova Estufa"
 * - Mostra apenas a bancada clicada
 */
function selectBancada(nomeBancada, idEstufa) {
    const nomeNormalizado = nomeBancada
        .toString()
        .trim()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/\s+/g, "-")
        .replace(/[^a-zA-Z0-9-_]/g, "");

    // Fecha todas as bancadas abertas
    document.querySelectorAll(".item-bancada-content").forEach(div => div.classList.add("d-none"));

    // Oculta todas as estufas e o bloco de nova estufa
    document.querySelectorAll(".item-estufa-box").forEach(div => div.classList.add("d-none"));
    const novaEstufa = document.getElementById("item-add-estufa");
    if (novaEstufa) novaEstufa.classList.add("d-none");

    // Mostra apenas a bancada clicada
    const box = document.getElementById(`item-bancada-${nomeBancada}-content-estufa-${idEstufa}`)
            || document.getElementById(`item-bancada-${nomeNormalizado}-content-estufa-${idEstufa}`);

    if (box) {
        box.classList.remove("d-none");
    } else {
        console.warn("⚠️ Bancada não encontrada:", nomeBancada, idEstufa);
    }
}

/**
 * Volta da bancada para a estufa
 * - Fecha todas as bancadas
 * - Reabre a estufa correspondente
 * - Mantém o botão "Fechar" ativo
 */
function voltarEstufa(idEstufa) {
    // Fecha todas as bancadas
    document.querySelectorAll(".item-bancada-content").forEach(div => div.classList.add("d-none"));

    // Mostra a estufa de origem
    const box = document.getElementById(`estufa-${idEstufa}-box`);
    if (box) box.classList.remove("d-none");

    // Restaura o botão
    const btn = document.getElementById(`edit-estufa-${idEstufa}`);
    if (btn) {
        btn.textContent = "Fechar";
        btn.classList.add("fechar");
    }

    // Garante que o "+ Nova Estufa" continue oculto
    const novaEstufa = document.getElementById("item-add-estufa");
    if (novaEstufa) novaEstufa.classList.add("d-none");
}
