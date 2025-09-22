<?php
// Middleware de autenticação para o Caderno (JWT HS256/RS256)

require_once __DIR__ . "/env.php";

// autoload do composer (firebase/php-jwt)
require_once __DIR__ . "/../vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Lê o token do cookie AUTH_COOKIE ou do header Authorization: Bearer
 */
function getBearerOrCookie() {
  if (!empty($_COOKIE[AUTH_COOKIE])) {
    return $_COOKIE[AUTH_COOKIE];
  }
  $h = $_SERVER["HTTP_AUTHORIZATION"] ?? "";
  if ($h && preg_match('/Bearer\s+(.+)/i', $h, $m)) {
    return $m[1];
  }
  return null;
}

/**
 * Decodifica e valida o JWT, retornando as claims (ou null se inválido/ausente)
 */
function current_user() {
  static $claims = null;
  if ($claims !== null) return $claims;

  $jwt = getBearerOrCookie();
  if (!$jwt) return null;

  try {
    if (defined("JWT_ALGO") && JWT_ALGO === "HS256") {
      // HS256: usa segredo compartilhado
      $claims = JWT::decode($jwt, new Key(JWT_SECRET, "HS256"));
    } else {
      // RS256: usa chave pública
      $pub = @file_get_contents(JWT_PUBLIC_KEY_PATH);
      if (!$pub) return null;
      $claims = JWT::decode($jwt, new Key($pub, "RS256"));
    }
    return $claims;
  } catch (Throwable $e) {
    return null;
  }
}

/**
 * Força login nas páginas internas.
 * Evita loop se já estiver em /login.php (deixe o /login.php renderizar o formulário).
 */
function require_login() {
  $u = current_user();
  if ($u) return $u;

  $path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?? "/";
  if (in_array($path, ["/login.php", "/index.php", "/"])) {
    return null; // já está na tela de login
}
  $next = $_GET["next"] ?? ($_SERVER["REQUEST_URI"] ?? "/");
  header("Location: /login.php?next=" . urlencode($next));
  exit;
}