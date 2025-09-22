<?php
// configuracao/session.php

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

if (empty($_SESSION['__last_regen']) || time() - $_SESSION['__last_regen'] > 600) {
  session_regenerate_id(true);
  $_SESSION['__last_regen'] = time();
}

// ajuste o nome do índice conforme seu login
function is_logged_in(): bool {
  return !empty($_SESSION['user_id']);
}

function require_login(): void {
  if (is_logged_in()) return;

  // Se já está na página de login (/index.php), não redireciona para evitar loop
  $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  $loginPath = '/index.php';

  if ($uri === $loginPath || $uri === '/') {
    return;
  }

  $next = urlencode($_SERVER['REQUEST_URI'] ?? '/');
  header("Location: {$loginPath}?next={$next}", true, 302);
  exit;
}
