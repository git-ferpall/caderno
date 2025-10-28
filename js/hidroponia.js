/**
 * HIDROPONIA.JS v2.4
 * Sistema Caderno de Campo - Frutag
 * Controle de cadastro, exibição e interação de Estufas e Bancadas
 * Atualizado em 2025-10-24
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
    const form = document.querySelector(`#item-add-bancada-estufa-${id}`);

    if (!form) {
      alert("Formulário de bancada não encontrado.");
      return;
    }

    const nomeInput = form.querySelector("#b-nome");
    const produtoSelect = form.querySelector("#b-produto");
    const obsInput = form.querySelector("#b-obs");

    if (!nomeInput || !produtoSelect) {
      alert("Campos obrigatórios não encontrados.");
      return;
    }

    const nome = nomeInput.value.trim();
    const produto_id = produtoSelect.value.trim();
    const obs = obsInput ? obsInput.value.trim() : "";

    if (!nome) {
      alert("Informe o nome/número da bancada");
      return;
    }

    if (!produto_id) {
      alert("Selecione o produto/cultura da bancada");
      return;
    }

    const res = await fetch("../funcoes/salvar_bancada.php", {
      method: "POST",
      body: new URLSearchParams({ estufa_id: id, nome, produto_id, obs })
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
 * Alterna abertura/fechamento de uma estufa
 * - Apenas uma aberta por vez
 * - Alterna "Selecionar" ↔ "Fechar"
 * - Esconde "+ Nova Estufa" se qualquer estufa estiver aberta
 */
function selectEstufa(idEstufa) {
    const box = document.getElementById(`estufa-${idEstufa}-box`);
    const btn = document.getElementById(`edit-estufa-${idEstufa}`);
    const formNovaEstufa = document.getElementById("add-estufa");

    if (!box || !btn) return;

    const isOpen = !box.classList.contains("d-none");

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
    } else {
        // Se estava fechada → abre
        box.classList.remove("d-none");
        btn.textContent = "Fechar";
        btn.classList.add("fechar");
    }

    // Verifica se há alguma estufa aberta
    const algumaAberta = Array.from(document.querySelectorAll(".item-estufa-box"))
        .some(div => !div.classList.contains("d-none"));

    // Mostra/esconde "+ Nova Estufa"
    if (formNovaEstufa) {
        if (algumaAberta) formNovaEstufa.classList.add("d-none");
        else formNovaEstufa.classList.remove("d-none");
    }
}

/**
 * Marca visualmente a bancada selecionada
 */
function destacarBancadaSelecionada(nomeBancada, idEstufa) {
    // Remove destaque de todas
    document.querySelectorAll(".item-bancada").forEach(btn => {
        btn.classList.remove("bancada-selecionada");
    });

    // Adiciona destaque apenas à bancada atual
    const btnAtual = document.getElementById(`item-bancada-${nomeBancada}-estufa-${idEstufa}`);
    if (btnAtual) {
        btnAtual.classList.add("bancada-selecionada");
    }
}

/**
 * Mostra o conteúdo da bancada selecionada
 * - Fecha todas as bancadas abertas
 * - Fecha outras estufas, mas mantém aberta a da bancada
 * - Esconde "+ Nova Estufa" e "+ Nova Bancada"
 * - Destaca visualmente a bancada ativa
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

    // Oculta todas as estufas, EXCETO a da bancada clicada
    document.querySelectorAll(".item-estufa-box").forEach(div => {
        if (!div.id.includes(`estufa-${idEstufa}-box`)) div.classList.add("d-none");
    });

    // Esconde "+ Nova Estufa"
    const formNovaEstufa = document.getElementById("add-estufa");
    if (formNovaEstufa) formNovaEstufa.classList.add("d-none");

    // Esconde "+ Nova Bancada" da estufa atual
    const formNovaBancada = document.getElementById(`add-bancada-estufa-${idEstufa}`);
    if (formNovaBancada) formNovaBancada.classList.add("d-none");

    // Mostra apenas a bancada clicada
    const box = document.getElementById(`item-bancada-${nomeBancada}-content-estufa-${idEstufa}`)
        || document.getElementById(`item-bancada-${nomeNormalizado}-content-estufa-${idEstufa}`);
    if (box) box.classList.remove("d-none");

    // Mantém o botão "Fechar" ativo
    const btn = document.getElementById(`edit-estufa-${idEstufa}`);
    if (btn) {
        btn.textContent = "Fechar";
        btn.classList.add("fechar");
    }

    // 🟢 Destaque visual
    destacarBancadaSelecionada(nomeBancada, idEstufa);
}

/**
 * Volta da bancada para a estufa
 * - Fecha todas as bancadas
 * - Reabre a estufa correspondente
 * - Mantém o botão "Fechar" ativo
 * - Restaura "+ Nova Bancada"
 * - Remove o destaque visual
 */
function voltarEstufa(idEstufa) {
    // Fecha todas as bancadas
    document.querySelectorAll(".item-bancada-content").forEach(div => div.classList.add("d-none"));

    // Remove destaque visual
    document.querySelectorAll(".item-bancada").forEach(btn => btn.classList.remove("bancada-selecionada"));

    // Mostra a estufa de origem
    const box = document.getElementById(`estufa-${idEstufa}-box`);
    if (box) box.classList.remove("d-none");

    // Mantém o botão "Fechar"
    const btn = document.getElementById(`edit-estufa-${idEstufa}`);
    if (btn) {
        btn.textContent = "Fechar";
        btn.classList.add("fechar");
    }

    // Mantém o "+ Estufa" oculto
    const formNovaEstufa = document.getElementById("add-estufa");
    if (formNovaEstufa) formNovaEstufa.classList.add("d-none");

    // 🔹 Restaura "+ Nova Bancada"
    const formNovaBancada = document.getElementById(`add-bancada-estufa-${idEstufa}`);
    if (formNovaBancada) formNovaBancada.classList.remove("d-none");
}
// === Carregar produtos (culturas) ===
async function carregarProdutos() {
  try {
    const resp = await fetch("../funcoes/buscar_produtos.php");
    const data = await resp.json();

    document.querySelectorAll(".produto-select").forEach(sel => {
      sel.innerHTML = '<option value="">Selecione o produto</option>';
      data.forEach(p => {
        const opt = document.createElement("option");
        opt.value = p.id;
        opt.textContent = p.nome;
        sel.appendChild(opt);
      });
    });
  } catch (err) {
    console.error("Erro ao carregar produtos:", err);
  }
}

// Executa o carregamento inicial
carregarProdutos();

// Caso o usuário adicione uma nova bancada dinamicamente
document.addEventListener("click", (e) => {
  if (e.target.id?.startsWith("bancada-add-estufa-")) {
    setTimeout(carregarProdutos, 300); // aguarda o campo aparecer
  }
});
