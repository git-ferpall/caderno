/**
 * CONTATO_CLIENTE.JS
 * -------------------
 * Gerencia o carregamento e salvamento de dados do contato do usuário.
 * -------------------
 */

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("perf-form");
  const btnSalvar = document.getElementById("form-save-perfil");
  const btnCancelar = document.getElementById("form-cancel-perfil");

  const inputNome = document.getElementById("pf-nome");
  const inputEmail = document.getElementById("pf-email");
  const inputTel = document.getElementById("pf-num1");

  // === 1️⃣ Carrega dados existentes ===
  async function carregarContato() {
    try {
      const resp = await fetch("../funcoes/buscar_contato.php", { cache: "no-store" });
      const data = await resp.json();

      inputNome.value = data?.nome || "";
      inputEmail.value = data?.email || "";
      inputTel.value = data?.telefone || "";
    } catch (err) {
      console.error("❌ Erro ao buscar contato:", err);
    }
  }

  // === 2️⃣ Salva dados ===
  async function salvarContato() {
    const formData = new FormData(form);

    try {
      const resp = await fetch("../funcoes/salvar_contato.php", {
        method: "POST",
        body: formData
      });
      const json = await resp.json();

      if (json.ok) {
        alert("✅ Dados salvos com sucesso!");
        // opcional: recarregar os dados do banco sem mostrar outro alerta
        carregarContato();
      } else {
        alert("⚠️ " + (json.msg || "Erro ao salvar os dados."));
      }
    } catch (err) {
      console.error("❌ Erro ao salvar contato:", err);
      alert("Erro ao salvar os dados.");
    }
  }

  // === 3️⃣ Reseta o formulário ===
  function limparCampos() {
    inputNome.value = "";
    inputEmail.value = "";
    inputTel.value = "";
  }

  // === 4️⃣ Eventos ===
  btnSalvar.addEventListener("click", salvarContato);
  btnCancelar.addEventListener("click", limparCampos);

  // === 5️⃣ Inicializa ===
  carregarContato();
});
