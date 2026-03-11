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

  // === Carregar ÁREAS ===
  function carregarAreas() {
    fetch("../funcoes/buscar_areas.php")
      .then(r => r.json())
      .then(data => {

        document.querySelectorAll(".area-select").forEach(sel => {

          const valorAtual = sel.value; // guarda seleção atual

          sel.innerHTML = '<option value="">Selecione a área</option>';

          data.forEach(item => {

            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = `${item.nome} (${item.tipo})`;

            // restaura seleção anterior
            if (item.id == valorAtual) {
              opt.selected = true;
            }

            sel.appendChild(opt);

          });

        });

      })
      .catch(err => console.error("Erro ao carregar áreas:", err));
  }

  carregarAreas();

  

  // === Carregar PRODUTOS ===
  // === Carregar PRODUTOS ===
  function carregarProdutos() {
    fetch("../funcoes/buscar_produtos.php")
      .then(r => r.json())
      .then(data => {

        document.querySelectorAll(".produto-select").forEach(sel => {

          const valorAtual = sel.value; // guarda seleção atual

          sel.innerHTML = '<option value="">Selecione o produto</option>';

          data.forEach(item => {

            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = item.nome;

            // restaura seleção anterior
            if (item.id == valorAtual) {
              opt.selected = true;
            }

            sel.appendChild(opt);

          });

        });

      })
      .catch(err => console.error("Erro ao carregar produtos:", err));
  }

  carregarProdutos();

  /* ===============================
  ADICIONAR ÁREA
  =============================== */

  const btnAddArea = document.querySelector(".add-area");

  if (btnAddArea) {

    btnAddArea.addEventListener("click", () => {

      const lista = document.getElementById("lista-areas");
      const original = lista.querySelector(".form-box-area");

      if (!original) return;

      const clone = original.cloneNode(true);
      const select = clone.querySelector("select");

      select.value = "";

      const btnRemover = document.createElement("button");
      btnRemover.type = "button";
      btnRemover.className = "remove-btn";
      btnRemover.innerHTML = "−";

      btnRemover.onclick = () => {

        const total = document.querySelectorAll("#lista-areas .form-box-area").length;

        if (total > 1) {
          clone.remove();
        } else {
          alert("É necessário manter pelo menos uma área.");
        }

      };

      clone.appendChild(btnRemover);

      lista.appendChild(clone);

      carregarAreas();

    });

  }


  /* ===============================
  ADICIONAR PRODUTO
  =============================== */

  const btnAddProduto = document.querySelector(".add-produto");

  if (btnAddProduto) {

    btnAddProduto.addEventListener("click", () => {

      const lista = document.getElementById("lista-produtos");
      const original = lista.querySelector(".form-box-produto");

      if (!original) return;

      const clone = original.cloneNode(true);
      const select = clone.querySelector("select");

      select.value = "";

      const btnRemover = document.createElement("button");
      btnRemover.type = "button";
      btnRemover.className = "remove-btn";
      btnRemover.innerHTML = "−";

      btnRemover.onclick = () => {

        const total = document.querySelectorAll("#lista-produtos .form-box-produto").length;

        if (total > 1) {
          clone.remove();
        } else {
          alert("É necessário manter pelo menos um produto.");
        }

      };

      clone.appendChild(btnRemover);

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