document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("form-save").addEventListener("click", salvarArea);
    document.getElementById("form-cancel").addEventListener("click", cancelarEdicao);
    document.getElementById("area-add").addEventListener("click", novaArea);

    atualizarTabelaAreas(); // carrega tabela ao abrir
});

function novaArea() {
    document.getElementById("area-id").value = "";
    document.getElementById("a-nome").value = "";
    document.querySelector('input[name="atipo"][value="1"]').checked = true;
    document.getElementById("item-add-area").style.display = "block";
}

function cancelarEdicao() {
    document.getElementById("add-area").reset();
    document.getElementById("item-add-area").style.display = "none";
}

function salvarArea() {
    const form = document.getElementById("add-area");
    const formData = new FormData(form);

    fetch("../funcoes/cadastra_area.php", {
        method: "POST",
        body: formData
    })
    .then(r => r.text())
    .then(resposta => {
        if (resposta.includes("sucesso")) {
            atualizarTabelaAreas();
            cancelarEdicao();
        } else {
            alert(resposta);
        }
    })
    .catch(err => console.error("Erro:", err));
}

function atualizarTabelaAreas() {
    fetch("../funcoes/busca_areas.php")
        .then(r => r.text())
        .then(html => {
            document.getElementById("tabela-areas").innerHTML = html;
        });
}

function editArea(area) {
    document.getElementById("area-add").click(); // 🔧 garante que o campo apareça

    document.getElementById("area-id").value = area.id;
    document.getElementById("a-nome").value = area.nome;
    document.querySelector(`input[name="atipo"][value="${area.tipo}"]`).checked = true;
}

function excluirArea(id) {
    if (!confirm("Deseja excluir esta área?")) return;

    fetch("../funcoes/exclui_area.php", {
        method: "POST",
        body: new URLSearchParams({ id })
    })
    .then(r => r.text())
    .then(resposta => {
        if (resposta.includes("sucesso")) {
            atualizarTabelaAreas();
        } else {
            alert(resposta);
        }
    });
}
