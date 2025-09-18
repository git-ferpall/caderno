document.addEventListener("DOMContentLoaded", () => {
    console.log("📦 produtos.js carregado");

    const btnSalvar = document.getElementById("form-save");
    const btnCancelar = document.getElementById("form-cancel");
    const btnNovoProduto = document.getElementById("produto-add");

    if (btnSalvar) btnSalvar.addEventListener("click", (e) => salvarProduto(e));
    if (btnCancelar) btnCancelar.addEventListener("click", cancelarEdicao);
    if (btnNovoProduto) btnNovoProduto.addEventListener("click", novoProduto);

    // Previna submissão automática do formulário
    const form = document.getElementById("add-produto");
    if (form) {
        form.addEventListener("submit", (e) => e.preventDefault());
    }

    console.log("🟢 DOM carregado");
    atualizarTabelaProdutos();
});

function novoProduto() {
    document.getElementById("item-add-produto").style.display = "block";
}

function cancelarEdicao() {
    document.getElementById("add-produto").reset();
    document.getElementById("item-add-produto").style.display = "none";

    // Remove campo oculto de edição, se existir
    const inputHidden = document.getElementById("produto-id");
    if (inputHidden) inputHidden.remove();
}

function salvarProduto(event) {
    if (event) event.preventDefault();

    const form = document.getElementById("add-produto");
    const formData = new FormData(form);

    fetch("../funcoes/cadastra_produto.php", {
        method: "POST",
        body: formData,
        credentials: "include"
    })
    .then(r => r.text())
    .then(resposta => {
        console.log("✅ Resposta do cadastro:", resposta);
        if (resposta.includes("sucesso")) {
            atualizarTabelaProdutos();
            cancelarEdicao();
        } else {
            alert(resposta);
        }
    })
    .catch(err => {
        console.error("❌ Erro ao salvar o produto:", err);
        alert("Erro ao salvar o produto.");
    });
}

function editItem(produto) {
    // Simula o clique no botão "Novo Produto"
    document.getElementById('produto-add').click();

    // Preenche os campos
    document.getElementById('p-nome').value = produto.nome;

    const tipoRadio = document.querySelector(`input[name="ptipo"][value="${produto.cultivo}"]`);
    if (tipoRadio) tipoRadio.checked = true;

    const atrRadio = document.querySelector(`input[name="patr"][value="${produto.atributo}"]`);
    if (atrRadio) atrRadio.checked = true;

    // Adiciona ou atualiza campo oculto com ID
    let inputHidden = document.getElementById("produto-id");
    if (!inputHidden) {
        inputHidden = document.createElement("input");
        inputHidden.type = "hidden";
        inputHidden.name = "produto_id";
        inputHidden.id = "produto-id";
        document.getElementById("add-produto").appendChild(inputHidden);
    }
    inputHidden.value = produto.id;
}
function excluirItem(id) {
    if (confirm("Tem certeza que deseja excluir este produto?")) {
        fetch("../funcoes/excluir_produto.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id=${id}`
        })
        .then(res => res.text())
        .then(resposta => {
            console.log("🗑️ Produto excluído:", resposta);
            if (resposta.includes("sucesso")) {
                atualizarTabelaProdutos();
            } else {
                alert(resposta);
            }
        })
        .catch(err => {
            console.error("❌ Erro ao excluir produto:", err);
            alert("Erro ao excluir produto.");
        });
    }
}
function atualizarTabelaProdutos() {
    console.log("🚀 Chamando busca_produtos.php...");
    fetch("../funcoes/busca_produtos.php", {
        method: "GET",
        credentials: "include"
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById("tabela-produtos").innerHTML = html;

        // Após carregar a tabela, reatribui eventos aos botões de excluir
        document.querySelectorAll(".btn-excluir").forEach(button => {
            button.addEventListener("click", function () {
                const id = this.getAttribute("data-id");
                excluirItem(id);
            });
        });
    })
    .catch(error => {
        console.error("❌ Erro ao buscar produtos:", error);
        alert("Erro ao buscar produtos.");
    });
}