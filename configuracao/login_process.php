<?php
require_once __DIR__ . '/../configuracao/env.php';
require_once __DIR__ . '/../include/http.php';

$login   = trim($_POST['login'] ?? '');
$senha   = trim($_POST['senha'] ?? '');
$next    = $_POST['next'] ?? '/';
$captcha = $_POST['g-recaptcha-response'] ?? '';

if ($login === '' || $senha === '') {
  header('Location: /login.php?e=1'); exit;
}

$resp = http_post_form(AUTH_API_LOGIN, [
  'login'   => $login,
  'senha'   => $senha,
  'captcha' => $captcha,
]);
$data = json_decode($resp, true);

if (!is_array($data) || empty($data['ok']) || empty($data['token'])) {
  header('Location: /login.php?e=2'); exit;
}

// Detecta se está em HTTPS (útil por causa do proxy)
$isHttps = (
  (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
  (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
  (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
);

$params = [
  'expires'  => time() + 3600, // 1h
  'path'     => '/',
  'secure'   => $isHttps,      // precisa ser true em produção
  'httponly' => true,
  'samesite' => $isHttps ? 'None' : 'Lax',
];

// Em produção (com HTTPS válido nos dois), fixe domínio para SSO:
if ($isHttps) {
  $params['domain'] = COOKIE_DOMAIN; // .frutag.com.br
} // Em ambiente de teste sem HTTPS, não setar domain/secure.

setcookie(AUTH_COOKIE, $data['token'], $params);

header('Location: ' . ($next ?: '/'));
exit;
