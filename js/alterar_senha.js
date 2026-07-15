/**
 * ALTERAR_SENHA.JS
 * ----------------
 * Troca de senha do usuário local na página de perfil.
 */

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("senha-form");
  if (!form) return; // usuário Frutag: seção não existe

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    try {
      const resp = await fetch("../funcoes/alterar_senha.php", {
        method: "POST",
        credentials: "same-origin",
        body: new FormData(form),
      });
      const json = await resp.json();
      alert(json.msg || (json.ok ? "Senha alterada." : "Erro ao alterar senha."));
      if (json.ok) form.reset();
    } catch (err) {
      console.error("Erro ao alterar senha:", err);
      alert("Erro de comunicação. Tente novamente.");
    }
  });
});
