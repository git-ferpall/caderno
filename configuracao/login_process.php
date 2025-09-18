<?php
require_once __DIR__ . "/env.php";   // mesma pasta
require_once __DIR__ . "/http.php";  // mesma pasta

\$login   = trim(\$_POST["login"] ?? "");
\$senha   = trim(\$_POST["senha"] ?? "");
\$next    = \$_POST["next"] ?? "/";
\$captcha = \$_POST["g-recaptcha-response"] ?? "";

if (\$login === "" || \$senha === "") { header("Location: /login.php?e=1"); exit; }

\$resp = http_post_form(AUTH_API_LOGIN, [
  "login"   => \$login,
  "senha"   => \$senha,
  "captcha" => \$captcha,
]);
\$data = json_decode(\$resp, true);

if (!is_array(\$data) || empty(\$data["ok"]) || empty(\$data["token"])) {
  header("Location: /login.php?e=2"); exit;
}

/* Durante os testes (sem HTTPS), NÃO use secure/domain */
\$params = [
  "expires"  => time() + 3600,
  "path"     => "/",
  "httponly" => true,
  "samesite" => "Lax",
];
setcookie(AUTH_COOKIE, \$data["token"], \$params);
header("Location: " . (\$next ?: "/")); exit;
PHP'
