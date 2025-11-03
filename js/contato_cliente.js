/**
 * CONTATO_CLIENTE.JS
 * -------------------
 * Gerencia o carregamento e salvamento de dados do contato do usuário.
 * Compatível com sessão PHP e JWT.
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

      console.log("✅ Dados carregados:", data);
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

      alert(json.msg || "Resposta desconhecida.");
      console.log("💾 Retorno:", json);

      if (json.ok) {
        carregarContato(); // Atualiza dados após salvar
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
