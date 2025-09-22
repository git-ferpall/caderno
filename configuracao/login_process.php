<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/auth.php'; // <- para usar sanitize_next() (abaixo)

$login = trim($_POST['login'] ?? '');
$senha = trim($_POST['senha'] ?? '');
$next  = $_POST['next'] ?? '/';

if ($login === '' || $senha === '') { header('Location: /index.php?e=1'); exit; }

$r = http_post_form(AUTH_API_LOGIN, ['login'=>$login,'senha'=>$senha]);

if (!$r || $r['status'] === 0 || $r['body'] === '' || $r['status'] >= 500) {
  header('Location: /index.php?e=api'); exit;
}

if ($r['status'] === 401 || $r['status'] === 403) {
  header('Location: /index.php?e=cred'); exit;
}

$j = json_decode($r['body'], true);
if (!is_array($j) || empty($j['ok']) || empty($j['token'])) {
  header('Location: /index.php?e=cred'); exit;
}

// grava o JWT — igual você já faz
setcookie(AUTH_COOKIE, $j['token'], [
  'expires'  => time()+3600,
  'path'     => '/',
  'domain'   => '.frutag.com.br',
  'secure'   => true,
  'httponly' => true,
  'samesite' => 'None', // exige HTTPS
]);

// redireciona para o destino seguro (sanitizado)
$next = sanitize_next($next);        // <- vem de auth.php
header('Location: ' . $next, true, 302);
exit;
