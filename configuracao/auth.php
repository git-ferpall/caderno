<?php
require_once __DIR__ . '/../configuracao/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function getBearerOrCookie() {
  if (!empty($_COOKIE[AUTH_COOKIE])) return $_COOKIE[AUTH_COOKIE];
  $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (preg_match('/Bearer\s+(.+)/i', $h, $m)) return $m[1];
  return null;
}

function current_user() {
  static $claims = null;
  if ($claims !== null) return $claims;

  $jwt = getBearerOrCookie();
  if (!$jwt) return null;

  $pub = @file_get_contents(JWT_PUBLIC_KEY_PATH);
  if (!$pub) return null;

  try {
    $claims = JWT::decode($jwt, new Key($pub, 'RS256'));
    return $claims; // ->sub (id no Frutag), ->tipo, ->name, ->email, etc.
  } catch (Throwable $e) {
    return null;
  }
}

function require_login() {
  $u = current_user();
  if ($u) return $u;
  header('Location: /?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
  exit;
}
