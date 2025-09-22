<?php
require_once __DIR__ . "/env.php";
require_once __DIR__ . "/../vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

const LOGIN_PATH = '/index.php';
const DEFAULT_AFTER_LOGIN = '/home/home.php';

function getBearerOrCookie() {
  if (!empty($_COOKIE[AUTH_COOKIE])) return $_COOKIE[AUTH_COOKIE];
  $h = $_SERVER["HTTP_AUTHORIZATION"] ?? "";
  if ($h && preg_match('/Bearer\s+(.+)/i', $h, $m)) return $m[1];
  return null;
}

function current_user() {
  static $claims = null;
  if ($claims !== null) return $claims;

  $jwt = getBearerOrCookie();
  if (!$jwt) return null;

  try {
    if (defined("JWT_ALGO") && JWT_ALGO === "HS256") {
      $claims = JWT::decode($jwt, new Key(JWT_SECRET, "HS256"));
    } else {
      $pub = @file_get_contents(JWT_PUBLIC_KEY_PATH);
      if (!$pub) return null;
      $claims = JWT::decode($jwt, new Key($pub, "RS256"));
    }
    return $claims;
  } catch (Throwable $e) {
    return null;
  }
}

if (!function_exists('isLogged')) {
  function isLogged(): bool {
    // mantém compatibilidade com qualquer sessão que você já use
    return current_user() !== null || !empty($_SESSION['user_id'] ?? null);
  }
}

// ── util: garante que "next" é um caminho interno
function sanitize_next($next) {
  $next = $next ?: DEFAULT_AFTER_LOGIN;
  if (preg_match('~^https?://~i', $next)) return DEFAULT_AFTER_LOGIN;
  if ($next[0] !== '/') $next = '/' . ltrim($next, '/');
  return $next;
}

function require_login() {
  if (defined('PUBLIC_PAGE') && PUBLIC_PAGE === true) return; // index.php é público
  if (isLogged()) return;

  $script = $_SERVER['SCRIPT_NAME'] ?? '/';
  $path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

  // evita loop no login
  if ($script === LOGIN_PATH || $path === LOGIN_PATH || $path === '/') return;

  // para AJAX, devolve 401 em vez de redirecionar
  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
  if ($isAjax) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'unauthenticated']);
    exit;
  }

  $next = sanitize_next($_SERVER['REQUEST_URI'] ?? DEFAULT_AFTER_LOGIN);
  header('Location: ' . LOGIN_PATH . '?next=' . urlencode($next), true, 302);
  exit;
}
