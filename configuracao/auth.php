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
  if (!empty($_COOKIE['AUTH_COOKIE'])) {
    return $_COOKIE['AUTH_COOKIE'];
  }
  if (!empty($_COOKIE['token'])) { // fallback para API que usa 'token'
    return $_COOKIE['token'];
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
  if (in_array($path, ["/login.php", "/login", "/index.php", "/index", "/"])) {
    return null; // já está na tela de login
}
  $next = $_GET["next"] ?? ($_SERVER["REQUEST_URI"] ?? "/");
  header("Location: /?next=" . urlencode($next));
  exit;
}

/**
 * Conexão mysqli sob demanda (para checagem de perfil fora das páginas
 * que já incluem configuracao_conexao.php).
 */
function caderno_db(): mysqli {
  if (!isset($GLOBALS['mysqli']) || !($GLOBALS['mysqli'] instanceof mysqli)) {
    require __DIR__ . '/configuracao_conexao.php'; // define $mysqli neste escopo
    $GLOBALS['mysqli'] = $mysqli;
    $GLOBALS['cnx'] = $GLOBALS['conexao'] = $GLOBALS['db'] = $mysqli;
  }
  require_once __DIR__ . '/usuarios_local.php';
  return $GLOBALS['mysqli'];
}

/**
 * Perfil efetivo do usuário logado, sempre relido do banco
 * ('usuario' | 'representante' | 'admin' | null se inativo/deslogado).
 * Usuário Frutag ainda não provisionado conta como 'usuario'.
 */
function caderno_user_perfil(): ?string {
  static $cache = false;
  if ($cache !== false) return $cache;

  $u = current_user();
  $id = (int)($u->sub ?? 0);
  if (!$id) return $cache = null;

  $db = caderno_db();
  $reg = usuarioBuscarPorId($db, $id);
  if ($reg) {
    return $cache = ((int)$reg['ativo'] === 1 ? $reg['perfil'] : null);
  }
  return $cache = 'usuario';
}

/**
 * Força login E um dos perfis informados. Para páginas HTML.
 */
function require_perfil(array $perfis) {
  $u = require_login();
  $perfil = caderno_user_perfil();
  if ($perfil === null || !in_array($perfil, $perfis, true)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Acesso negado</title></head><body style="font-family:sans-serif;padding:40px;text-align:center"><h1>Acesso negado</h1><p>Você não tem permissão para acessar esta área.</p><p><a href="/home">Voltar</a></p></body></html>';
    exit;
  }
  return $u;
}

function require_admin() {
  return require_perfil(['admin']);
}