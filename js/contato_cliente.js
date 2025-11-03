/**
 * CONTATO_CLIENTE.JS v3
 * ----------------------
 * Evita alertas duplicados ("✅ Dados salvos com sucesso!")
 * e impede duplo clique no botão Salvar.
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

  // ⚙️ Garante que o formulário não recarregue a página
  form.addEventListener("submit", (e) => e.preventDefault());

  // === 1️⃣ Carregar dados existentes ===
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

  // === 2️⃣ Salvar dados (com bloqueio de duplo clique) ===
  let salvando = false;
  async function salvarContato(event) {
    event.preventDefault();

    if (salvando) return; // impede duplo clique
    salvando = true;
    btnSalvar.disabled = true;

    const formData = new FormData(form);

    try {
      const resp = await fetch("../funcoes/salvar_contato.php", {
        method: "POST",
        body: formData
      });
      const json = await resp.json();

      if (json.ok) {
        alert("✅ Dados salvos com sucesso!");
        await carregarContato();
      } else {
        alert("⚠️ " + (json.msg || "Erro ao salvar dados."));
      }
    } catch (err) {
      console.error("❌ Erro ao salvar contato:", err);
      alert("Erro ao salvar os dados.");
    } finally {
      salvando = false;
      btnSalvar.disabled = false;
    }
  }

  // === 3️⃣ Limpar campos ===
  function limparCampos() {
    inputNome.value = "";
    inputEmail.value = "";
    inputTel.value = "";
    chkEmail.checked = false;
    chkSms.checked = false;
  }

  // === 4️⃣ Eventos (garante que só existe UM listener ativo) ===
  btnSalvar.replaceWith(btnSalvar.cloneNode(true)); // remove listeners antigos
  const newBtnSalvar = document.getElementById("form-save-perfil");
  newBtnSalvar.addEventListener("click", salvarContato);

  btnCancelar.addEventListener("click", limparCampos);

  // === 5️⃣ Inicializa ===
  carregarContato();
});
