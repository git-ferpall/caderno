<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/http.php';

$login = trim($_POST['login'] ?? '');
$senha = trim($_POST['senha'] ?? '');
$next  = $_POST['next']  ?? '/';
$captcha = trim($_POST['g-recaptcha-response'] ?? ''); // 👈 pegue o reCAPTCHA

if ($login === '' || $senha === '') { header('Location: /index.php?e=1'); exit; }

/**
 * Monte o payload usando os nomes que sua API espera.
 * Tente primeiro com 'login'/'senha' + captcha. Se sua API usa 'email'/'password',
 * troque as chaves abaixo sem dó.
 */
$payload = [
  'login' => $login,                  // troque para 'email' se a API usar esse nome
  'senha' => $senha,                  // troque para 'password' se a API usar esse nome
  'g-recaptcha-response' => $captcha, // muitas APIs exigem
];

// ====== CHAMADA DA API ======
// 1) Tente via form-encoded (geralmente funciona):
$r = http_post_form(AUTH_API_LOGIN, $payload);

// Se sua função http_post_form não existe/é limitada, use um fallback em JSON:
/*
$ch = curl_init(AUTH_API_LOGIN);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 15,
]);
$body = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$r = ['status' => $http, 'body' => $body];
*/

// ====== TRATAMENTO ======
if (!$r || ($r['status'] ?? 0) === 0 || ($r['body'] ?? '') === '' || ($r['status'] ?? 0) >= 500) {
  error_log("AUTH_API erro rede/5xx status=" . ($r['status'] ?? 'null'));
  header('Location: /index.php?e=api'); exit;
}

if (($r['status'] ?? 0) === 401 || ($r['status'] ?? 0) === 403) {
  error_log("AUTH_API 401/403 body=" . substr($r['body'],0,400));
  header('Location: /index.php?e=cred'); exit;
}

$j = json_decode($r['body'], true);
if (!is_array($j) || empty($j['ok']) || empty($j['token'])) {
  error_log("AUTH_API sem ok/token body=" . substr($r['body'],0,400));
  header('Location: /index.php?e=cred'); exit;
}

// ====== COOKIE JWT ======
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
$cookieOptions = [
  'expires'  => time() + 3600,
  'path'     => '/',
  'httponly' => true,
  'samesite' => 'Lax',
  'secure'   => $isHttps,
];
// não force 'domain' — deixe o host atual (evita rejeição do navegador)
setcookie(AUTH_COOKIE, $j['token'], $cookieOptions);
error_log("AUTH_COOKIE setado (secure=" . ($cookieOptions['secure']?'1':'0') . ") host=" . ($_SERVER['HTTP_HOST'] ?? ''));

header('Location: ' . ($next ?: '/')); exit;
