document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-erradicacao");
  const qtdInput = document.getElementById("quantidade");
  const aviso = document.getElementById("aviso-status");

  // === Aviso de status conforme quantidade ===
  if (qtdInput) {
    const atualizarAviso = () => {
      const val = qtdInput.value.trim();
      if (val === "" || parseInt(val) === 0) {
        aviso.textContent = "⚠ Deixe o campo vazio ou zero para manter o apontamento PENDENTE.";
        aviso.style.color = "orange";
      } else {
        aviso.textContent = "✔ Com quantidade informada, o status será CONCLUÍDO.";
        aviso.style.color = "green";
      }
    };
    atualizarAviso();
    qtdInput.addEventListener("input", atualizarAviso);
  }

  // === Carregar Áreas ===
  function carregarAreas() {
    fetch("../funcoes/buscar_areas.php")
      .then(r => r.json())
      .then(data => {
        document.querySelectorAll(".area-select").forEach(sel => {
          const selecionado = sel.value;
          sel.innerHTML = '<option value="">Selecione a área</option>';
          data.forEach(a => {
            const opt = document.createElement("option");
            opt.value = a.id;
            opt.textContent = `${a.nome} (${a.tipo})`;
            if (a.id == selecionado) opt.selected = true;
            sel.appendChild(opt);
          });
        });
      })
      .catch(err => console.error("Erro ao carregar áreas:", err));
  }

  // === Carregar Produtos ===
  function carregarProdutos() {
    fetch("../funcoes/buscar_produtos.php")
      .then(r => r.json())
      .then(data => {
        document.querySelectorAll(".produto-select").forEach(sel => {
          const selecionado = sel.value;
          sel.innerHTML = '<option value="">Selecione o produto</option>';
          data.forEach(p => {
            const opt = document.createElement("option");
            opt.value = p.id;
            opt.textContent = p.nome;
            if (p.id == selecionado) opt.selected = true;
            sel.appendChild(opt);
          });
        });
      })
      .catch(err => console.error("Erro ao carregar produtos:", err));
  }

  carregarAreas();
  carregarProdutos();

  // === Botão adicionar área ===
  const btnAddArea = document.querySelector(".add-area");
  if (btnAddArea) {
    btnAddArea.addEventListener("click", () => {
      const lista = document.getElementById("lista-areas");
      const original = lista.querySelector(".form-box-area");
      if (!original) return;

      const clone = original.cloneNode(true);
      const select = clone.querySelector("select");
      select.value = "";
      lista.appendChild(clone);
      carregarAreas();
    });
  }

  // === Botão adicionar produto ===
  const btnAddProduto = document.querySelector(".add-produto");
  if (btnAddProduto) {
    btnAddProduto.addEventListener("click", () => {
      const lista = document.getElementById("lista-produtos");
      const original = lista.querySelector(".form-box-produto");
      if (!original) return;

      const clone = original.cloneNode(true);
      const select = clone.querySelector("select");
      select.value = "";
      lista.appendChild(clone);
      carregarProdutos();
    });
  }

  // === Submit do formulário ===
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const dados = new FormData(form);

      try {
        const resp = await fetch("../funcoes/salvar_erradicacao.php", {
          method: "POST",
          body: dados
        });

        const res = await resp.json();

        if (res.ok) {
          showPopup("success", res.msg || "Erradicação registrada com sucesso!");

          setTimeout(() => {
            window.location.href = "apontamento.php";
          }, 1200);

        } else {
          showPopup("failed", res.msg || "Erro ao salvar erradicação.");
        }

      } catch (err) {
        console.error("Erro:", err);
        showPopup("failed", "Erro inesperado ao salvar apontamento.");
      }
    });
  }
});

function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");

  document.querySelectorAll(".popup-box").forEach(p => p.classList.add("d-none"));
  overlay?.classList.remove("d-none");

  if (tipo === "success") {
    popupSuccess?.classList.remove("d-none");
    popupSuccess.querySelector(".popup-title").textContent = mensagem;
  } else {
    popupFailed?.classList.remove("d-none");
    popupFailed.querySelector(".popup-title").textContent = mensagem;
  }
}