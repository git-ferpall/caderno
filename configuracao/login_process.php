<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/http.php';

$login = trim($_POST['login'] ?? '');
$senha = trim($_POST['senha'] ?? '');
$next  = $_POST['next'] ?? '/';

if ($login === '' || $senha === '') { header('Location: /login.php?e=1'); exit; }

$r = http_post_form(AUTH_API_LOGIN, ['login'=>$login,'senha'=>$senha]);

// Falha de rede, 5xx ou corpo vazio => erro de API
if (!$r || $r['status'] === 0 || $r['status'] >= 500 || $r['body'] === '') {
  header('Location: /login.php?e=api'); exit;
}

// 401 (ou 403) => credenciais inválidas
if ($r['status'] === 401 || $r['status'] === 403) {
  header('Location: /login.php?e=cred'); exit;
}

// Demais casos: tenta parsear JSON
$j = json_decode($r['body'], true);
if (!is_array($j) || empty($j['ok']) || empty($j['token'])) {
  header('Location: /login.php?e=cred'); exit;
}

// Cookie SSO seguro
setcookie(AUTH_COOKIE, $j['token'], [
  'expires'  => time()+3600,
  'path'     => '/',
  'domain'   => '.frutag.com.br',
  'secure'   => true,
  'httponly' => true,
  'samesite' => 'None',
]);

header('Location: ' . ($next ?: '/')); exit;
