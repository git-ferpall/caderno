<?php
require_once __DIR__ . '/../configuracao/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function current_user() {
  static $claims = null; if ($claims !== null) return $claims;
  $jwt = getBearerOrCookie(); if (!$jwt) return null;

  try {
    if (defined('JWT_ALGO') && JWT_ALGO === 'HS256') {
      $claims = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
    } else {
      $pub = @file_get_contents(JWT_PUBLIC_KEY_PATH);
      if (!$pub) return null;
      $claims = JWT::decode($jwt, new Key($pub, 'RS256'));
    }
    return $claims;
  } catch (Throwable $e) {
    return null;
  }
}

function require_login() {
  $u = current_user();
  if ($u) return $u;

  $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
  if ($path === '/login.php') {
    return null; // já está na tela de login: não redireciona de novo
  }

  $next = $_GET['next'] ?? ($_SERVER['REQUEST_URI'] ?? '/');
  header('Location: /login.php?next=' . urlencode($next));
  exit;
}
