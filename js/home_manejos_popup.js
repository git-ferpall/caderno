document.addEventListener("DOMContentLoaded", () => {
  const overlay = document.getElementById("popup-overlay");
  const popupDetalhe = document.getElementById("popup-detalhe-manejo");
  const btnConcluir = document.getElementById("btn-marcar-concluido");

  // Função global (pode ser chamada do outro JS)
  window.inicializarPopupLinhas = function () {
    const linhas = document.querySelectorAll(".apontamento-fazer tbody tr");

    linhas.forEach((tr) => {
      tr.addEventListener("click", () => {
        const item = {
          id: tr.dataset.id,
          data: tr.children[0].textContent,
          tipo: tr.children[1].textContent,
          areas: tr.children[2].textContent,
          produto: tr.children[3].textContent,
        };

        abrirPopupManejo(item);
      });
    });
  };

  // Exibe o popup com os dados do manejo
  function abrirPopupManejo(item) {
    document.getElementById("manejo-data").value = item.data;
    document.getElementById("manejo-tipo").value = item.tipo;
    document.getElementById("manejo-area").value = item.areas;
    document.getElementById("manejo-produto").value = item.produto;

    overlay.classList.remove("d-none");
    popupDetalhe.classList.remove("d-none");

    btnConcluir.onclick = () => marcarComoConcluido(item.id);
  }

  // Envia para o backend o pedido de marcação como concluído
  function marcarComoConcluido(id) {
    if (!id) return;

    fetch("../funcoes/marcar_concluido.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.ok) {
          closePopup();
          const popupSuccess = document.getElementById("popup-success");
          overlay.classList.remove("d-none");
          popupSuccess.classList.remove("d-none");
          popupSuccess.querySelector(".popup-title").textContent =
            "Manejo marcado como concluído!";
          document
            .getElementById("btn-ok")
            .addEventListener("click", () => location.reload(), { once: true });
        } else {
          alert(data.msg || "Erro ao marcar como concluído.");
        }
      })
      .catch((err) => alert("Erro: " + err));
  }
});
