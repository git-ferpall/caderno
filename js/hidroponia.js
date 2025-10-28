/**
 * HIDROPONIA.JS v2.5
 * Sistema Caderno de Campo - Frutag
 * Controle de cadastro, exibição e interação de Estufas e Bancadas
 * Atualizado em 2025-10-28
 */

document.addEventListener("DOMContentLoaded", () => {

  // 🟢 Adicionar nova estufa
  const btnAddEstufa = document.getElementById("form-save-estufa");
  if (btnAddEstufa) {
    btnAddEstufa.addEventListener("click", async () => {
      const nome = document.getElementById("e-nome").value.trim();
      const area = document.getElementById("e-area").value.trim();
      const obs = document.getElementById("e-obs").value.trim();

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
  }

  // 🟢 Adicionar nova bancada
document.querySelectorAll("[id^='form-save-bancada-estufa-']").forEach(btn => {
    btn.addEventListener("click", async e => {
        const id = e.target.id.split("-").pop();
        const container = document.querySelector(`#item-add-bancada-estufa-${id}`);

        // captura correta dos campos existentes no HTML
        const nome = container.querySelector("#b-nome").value.trim();
        const produto_id = container.querySelector("#b-produto").value.trim();
        const obs = container.querySelector("#b-obs").value.trim();

        if (!nome) {
            alert("Informe o nome/número da bancada");
            return;
        }

        if (!produto_id) {
            alert("Selecione o produto (cultura)");
            return;
        }

        const res = await fetch("../funcoes/salvar_bancada.php", {
            method: "POST",
            body: new URLSearchParams({ estufa_id: id, nome, produto_id, obs })
        });
        const data = await res.json();

        if (data.ok) {
            alert("✅ Bancada salva com sucesso!");
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

function selectEstufa(idEstufa) {
  const box = document.getElementById(`estufa-${idEstufa}-box`);
  const btn = document.getElementById(`edit-estufa-${idEstufa}`);
  const formNovaEstufa = document.getElementById("add-estufa");

  if (!box || !btn) return;

  const isOpen = !box.classList.contains("d-none");

  document.querySelectorAll(".item-estufa-box").forEach(div => div.classList.add("d-none"));
  document.querySelectorAll(".edit-btn").forEach(b => {
    b.textContent = "Selecionar";
    b.classList.remove("fechar");
  });

  if (isOpen) {
    box.classList.add("d-none");
    btn.textContent = "Selecionar";
    btn.classList.remove("fechar");
  } else {
    box.classList.remove("d-none");
    btn.textContent = "Fechar";
    btn.classList.add("fechar");
  }

  const algumaAberta = Array.from(document.querySelectorAll(".item-estufa-box"))
    .some(div => !div.classList.contains("d-none"));

  if (formNovaEstufa) {
    if (algumaAberta) formNovaEstufa.classList.add("d-none");
    else formNovaEstufa.classList.remove("d-none");
  }
}

function destacarBancadaSelecionada(nomeBancada, idEstufa) {
  document.querySelectorAll(".item-bancada").forEach(btn => {
    btn.classList.remove("bancada-selecionada");
  });

  const btnAtual = document.getElementById(`item-bancada-${nomeBancada}-estufa-${idEstufa}`);
  if (btnAtual) btnAtual.classList.add("bancada-selecionada");
}

function selectBancada(nomeBancada, idEstufa) {
  const nomeNormalizado = nomeBancada
    .toString()
    .trim()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/\s+/g, "-")
    .replace(/[^a-zA-Z0-9-_]/g, "");

  document.querySelectorAll(".item-bancada-content").forEach(div => div.classList.add("d-none"));

  document.querySelectorAll(".item-estufa-box").forEach(div => {
    if (!div.id.includes(`estufa-${idEstufa}-box`)) div.classList.add("d-none");
  });

  const formNovaEstufa = document.getElementById("add-estufa");
  if (formNovaEstufa) formNovaEstufa.classList.add("d-none");

  const formNovaBancada = document.getElementById(`add-bancada-estufa-${idEstufa}`);
  if (formNovaBancada) formNovaBancada.classList.add("d-none");

  const box = document.getElementById(`item-bancada-${nomeBancada}-content-estufa-${idEstufa}`)
    || document.getElementById(`item-bancada-${nomeNormalizado}-content-estufa-${idEstufa}`);
  if (box) box.classList.remove("d-none");

  const btn = document.getElementById(`edit-estufa-${idEstufa}`);
  if (btn) {
    btn.textContent = "Fechar";
    btn.classList.add("fechar");
  }

  destacarBancadaSelecionada(nomeBancada, idEstufa);
}

function voltarEstufa(idEstufa) {
  document.querySelectorAll(".item-bancada-content").forEach(div => div.classList.add("d-none"));
  document.querySelectorAll(".item-bancada").forEach(btn => btn.classList.remove("bancada-selecionada"));

  const box = document.getElementById(`estufa-${idEstufa}-box`);
  if (box) box.classList.remove("d-none");

  const btn = document.getElementById(`edit-estufa-${idEstufa}`);
  if (btn) {
    btn.textContent = "Fechar";
    btn.classList.add("fechar");
  }

  const formNovaEstufa = document.getElementById("add-estufa");
  if (formNovaEstufa) formNovaEstufa.classList.add("d-none");

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

carregarProdutos();

document.addEventListener("click", (e) => {
  if (e.target.id?.startsWith("bancada-add-estufa-")) {
    setTimeout(carregarProdutos, 300);
  }
});
