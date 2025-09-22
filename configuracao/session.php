<?php
// ../configuracao/session.php

// Cookies de sessão mais seguros
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'domain'   => '',
  'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443),
  'httponly' => true,
  'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Renovação periódica do ID (mitiga fixation)
if (empty($_SESSION['__last_regen']) || time() - $_SESSION['__last_regen'] > 600) {
  session_regenerate_id(true);
  $_SESSION['__last_regen'] = time();
}

function is_logged_in(): bool {
  // ajuste o campo conforme seu login (ex.: user_id, usuario_id, etc.)
  return !empty($_SESSION['user_id']);
}

function require_login(): void {
  if (!is_logged_in()) {
    $next = urlencode($_SERVER['REQUEST_URI'] ?? '/');
    // ajuste a rota de login se for diferente
    header("Location: /login/index.php?next={$next}", true, 302);
    exit;
  }
}
