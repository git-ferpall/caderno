document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-colheita");
  const qtdInput = document.getElementById("quantidade");

  // === Aviso status conforme quantidade ===
  if (qtdInput) {
    const avisoQtd = document.createElement("small");
    avisoQtd.style.display = "block";
    avisoQtd.style.marginTop = "4px";
    avisoQtd.style.fontSize = "0.9em";
    qtdInput.parentElement.appendChild(avisoQtd);

    const atualizarAviso = () => {
      if (qtdInput.value.trim() === "") {
        avisoQtd.textContent = "⚠ Para deixar o apontamento PENDENTE, mantenha este campo vazio.";
        avisoQtd.style.color = "orange";
      } else {
        avisoQtd.textContent = "✔ Com quantidade informada, o status será CONCLUÍDO.";
        avisoQtd.style.color = "green";
      }
    };

    atualizarAviso();
    qtdInput.addEventListener("input", atualizarAviso);
  }

  // === Funções para carregar selects ===
  function carregarAreas() {
    fetch("../funcoes/buscar_areas.php")
      .then(r => r.json())
      .then(data => {
        document.querySelectorAll(".area-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione a área</option>';
          data.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = item.nome;
            sel.appendChild(opt);
          });
        });
      });
  }

  function carregarProdutos() {
    fetch("../funcoes/buscar_produtos.php")
      .then(r => r.json())
      .then(data => {
        document.querySelectorAll(".produto-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione o produto</option>';
          data.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = item.nome;
            sel.appendChild(opt);
          });
        });
      });
  }

  // === Botão adicionar área ===
  document.querySelector(".add-area").addEventListener("click", () => {
    const lista = document.getElementById("lista-areas");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);

    novo.value = "";
    novo.removeAttribute("id");
    novo.name = "area[]";
    novo.classList.add("area-select");

    const wrapper = document.createElement("div");
    wrapper.className = "form-box form-box-area";
    wrapper.appendChild(novo);

    lista.appendChild(wrapper);
    carregarAreas();
  });

  // === Botão adicionar produto ===
  document.querySelector(".add-produto").addEventListener("click", () => {
    const lista = document.getElementById("lista-produtos");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);

    novo.value = "";
    novo.removeAttribute("id");
    novo.name = "produto[]";
    novo.classList.add("produto-select");

    const wrapper = document.createElement("div");
    wrapper.className = "form-box form-box-produto";
    wrapper.appendChild(novo);

    lista.appendChild(wrapper);
    carregarProdutos();
  });

  // === Carregar selects iniciais ===
  carregarAreas();
  carregarProdutos();

  // === Submit do formulário ===
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const formData = new FormData(form);

      try {
        const resp = await fetch("../funcoes/salvar_colheita.php", {
          method: "POST",
          body: formData
        });
        const data = await resp.json();

        if (data && data.ok === true) {
          showPopup("success", data.msg || "Dados Salvos Com Sucesso!");

          setTimeout(() => {
            window.location.href = "apontamento.php";
          }, 1200);

        } else {
          showPopup("failed", data.msg || "Erro ao salvar visita.");
        }

      } catch (err) {
        showPopup("failed", "Erro inesperado ao salvar visita.");
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