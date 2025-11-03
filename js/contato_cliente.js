/**
 * CONTATO_CLIENTE.JS v2
 * ----------------------
 * Gerencia o carregamento e salvamento de dados do contato do usuário.
 * Agora com aviso único e sem duplo envio.
 * ----------------------
 */

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("perf-form");
  const btnSalvar = document.getElementById("form-save-perfil");
  const btnCancelar = document.getElementById("form-cancel-perfil");

  const inputNome = document.getElementById("pf-nome");
  const inputEmail = document.getElementById("pf-email");
  const inputTel = document.getElementById("pf-num1");
  const chkEmail = document.getElementById("aceita_email");
  const chkSms = document.getElementById("aceita_sms");

  // Evita que o formulário envie sozinho
  form.addEventListener("submit", (e) => e.preventDefault());

  // === 1️⃣ Carrega dados existentes ===
  async function carregarContato() {
    try {
      const resp = await fetch("../funcoes/buscar_contato.php", { cache: "no-store" });
      const data = await resp.json();

      inputNome.value = data?.nome || "";
      inputEmail.value = data?.email || "";
      inputTel.value = data?.telefone || "";
      chkEmail.checked = data?.aceita_email == 1;
      chkSms.checked = data?.aceita_sms == 1;
    } catch (err) {
      console.error("❌ Erro ao buscar contato:", err);
    }
  }

  // === 2️⃣ Salva dados ===
  async function salvarContato(e) {
    e.preventDefault(); // evita duplo envio

    const formData = new FormData(form);

    try {
      const resp = await fetch("../funcoes/salvar_contato.php", {
        method: "POST",
        body: formData
      });
      const json = await resp.json();

      if (json.ok) {
        alert("✅ Dados salvos com sucesso! remover isso que inferno");
        carregarContato();
      } else {
        alert("⚠️ " + (json.msg || "Erro ao salvar dados."));
      }
    } catch (err) {
      console.error("❌ Erro ao salvar contato:", err);
      alert("Erro ao salvar os dados.");
    }
  }

  // === 3️⃣ Limpar ===
  function limparCampos() {
    inputNome.value = "";
    inputEmail.value = "";
    inputTel.value = "";
    chkEmail.checked = false;
    chkSms.checked = false;
  }

  // === 4️⃣ Eventos ===
  btnSalvar.addEventListener("click", salvarContato);
  btnCancelar.addEventListener("click", limparCampos);

  // === 5️⃣ Inicializa ===
  carregarContato();
});
