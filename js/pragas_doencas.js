document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-pragas");
  const campoAcao = document.getElementById("acao_corretiva");
  const aviso = document.getElementById("aviso-status");

  // === Atualiza o aviso conforme preenchimento ===
  if (campoAcao) {
    const atualizarAviso = () => {
      const texto = campoAcao.value.trim();
      if (texto === "") {
        aviso.textContent = "⚠ Deixe o campo de ação corretiva vazio para manter o status PENDENTE.";
        aviso.style.color = "orange";
      } else {
        aviso.textContent = "✔ Com ação corretiva informada, o status será CONCLUÍDO.";
        aviso.style.color = "green";
      }
    };
    campoAcao.addEventListener("input", atualizarAviso);
    atualizarAviso();
  }

  // === Carregar Áreas ===
  function carregarAreas() {
    fetch("../funcoes/buscar_areas.php")
      .then(r => r.json())
      .then(data => {
        document.querySelectorAll(".area-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione a área</option>';
          data.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = `${item.nome} (${item.tipo})`;
            sel.appendChild(opt);
          });
        });
      });
  }

  // === Carregar Produtos ===
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

  carregarAreas();
  carregarProdutos();

  // === Botão adicionar área ===
  document.querySelector(".add-area").addEventListener("click", () => {
    const lista = document.getElementById("lista-areas");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);
    novo.value = "";
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
    novo.name = "produto[]";
    novo.classList.add("produto-select");

    const wrapper = document.createElement("div");
    wrapper.className = "form-box form-box-produto";
    wrapper.appendChild(novo);
    lista.appendChild(wrapper);
    carregarProdutos();
  });

  // === Submit ===
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const dados = new FormData(form);

      try {
        const resp = await fetch("../funcoes/salvar_pragas_doencas.php", {
          method: "POST",
          body: dados
        });

        const res = await resp.json();

        if (res.ok) {
          showPopup("success", res.msg || "Apontamento de pragas/doenças salvo com sucesso!");

          setTimeout(() => {
            window.location.href = "apontamento.php";
          }, 1200);

        } else {
          showPopup("failed", res.msg || "Erro ao salvar apontamento.");
        }

      } catch (err) {
        showPopup("failed", "Erro inesperado ao salvar apontamento.");
      }
    });
  }
});

// === Popup padrão ===
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
