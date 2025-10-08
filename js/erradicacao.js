document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-erradicacao");
  const qtdInput = document.getElementById("quantidade");
  const aviso = document.getElementById("aviso-status");

  // Aviso dinâmico de status
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

  // Carregar Áreas
  function carregarAreas() {
    fetch("../funcoes/buscar_areas.php")
      .then(r => r.json())
      .then(data => {
        document.querySelectorAll(".area-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione a área</option>';
          data.forEach(a => {
            const opt = document.createElement("option");
            opt.value = a.id;
            opt.textContent = `${a.nome} (${a.tipo})`;
            sel.appendChild(opt);
          });
        });
      });
  }

  // Carregar Produtos
  function carregarProdutos() {
    fetch("../funcoes/buscar_produtos.php")
      .then(r => r.json())
      .then(data => {
        document.querySelectorAll(".produto-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione o produto</option>';
          data.forEach(p => {
            const opt = document.createElement("option");
            opt.value = p.id;
            opt.textContent = p.nome;
            sel.appendChild(opt);
          });
        });
      });
  }

  carregarAreas();
  carregarProdutos();

  // Adicionar Área
  document.querySelector(".add-area").addEventListener("click", () => {
    const lista = document.getElementById("lista-areas");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);
    novo.value = "";
    novo.name = "area[]";
    lista.appendChild(novo.parentElement.cloneNode(true));
    carregarAreas();
  });

  // Adicionar Produto
  document.querySelector(".add-produto").addEventListener("click", () => {
    const lista = document.getElementById("lista-produtos");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);
    novo.value = "";
    novo.name = "produto[]";
    lista.appendChild(novo.parentElement.cloneNode(true));
    carregarProdutos();
  });

  // Envio
  if (form) {
    form.addEventListener("submit", async e => {
      e.preventDefault();
      const dados = new FormData(form);

      try {
        const resp = await fetch("../funcoes/salvar_erradicacao.php", {
          method: "POST",
          body: dados
        });
        const res = await resp.json();

        if (res.ok) {
          showPopup("sucesso", res.msg);
          form.reset();
          carregarAreas();
          carregarProdutos();
        } else {
          showPopup("erro", res.msg);
        }
      } catch (err) {
        showPopup("erro", "Erro inesperado ao salvar apontamento.");
      }
    });
  }
});

function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");
  const popup = (tipo === "sucesso") ? popupSuccess : popupFailed;
  overlay.classList.remove("d-none");
  popup.classList.remove("d-none");
  const msgBox = popup.querySelector(".popup-text") || popup.querySelector(".popup-title");
  msgBox.textContent = mensagem;
  popup.querySelector(".popup-btn").onclick = () => {
    overlay.classList.add("d-none");
    popup.classList.add("d-none");
  };
}
