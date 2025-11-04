<?php
/**
 * /configuracao/sso_autologin.php
 * Integração SSO Frutag → Caderno de Campo
 * Agora executa via navegador, respeitando os cookies de sessão reais.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@session_start();

// 🔍 Token recebido (não é usado, mas deixamos para compatibilidade)
$token = $_GET['token'] ?? null;

// 🔗 Endpoint remoto (será consultado pelo navegador)
$api_url = "https://frutag.com.br/sso/userinfo.php";

// 🔧 Script JS para fazer a chamada via navegador (mantém os cookies)
echo <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Autenticando...</title>
</head>
<body>
<script>
(async () => {
  try {
    const resp = await fetch("$api_url", { credentials: 'include' });
    const data = await resp.json();

    if (!data.ok || !data.user) {
      document.body.innerHTML = '<h3 style="color:red;">Usuário não autenticado ou sessão expirada.</h3>';
      return;
    }

    // ✅ Envia os dados do usuário ao backend local do Caderno
    await fetch('/sso/login_cookie.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data.user)
    });

    // 🔁 Redireciona para o painel principal
    window.location.href = '/home/index.php';
  } catch (e) {
    document.body.innerHTML = '<h3 style="color:red;">Falha na autenticação: ' + e + '</h3>';
  }
})();
</script>
</body>
</html>
HTML;
