<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/recaptcha.php'; // 🔒 adiciona o arquivo com suas chaves

session_start();

$login   = trim($_POST['login'] ?? '');
$senha   = trim($_POST['senha'] ?? '');
$next    = $_POST['next'] ?? '/';
$captcha = trim($_POST['g-recaptcha-response'] ?? ''); // token do reCAPTCHA

if ($login === '' || $senha === '') {
    header('Location: /index.php?e=1');
    exit;
}

/**
 * ==============================================
 * 1️⃣  Validação reCAPTCHA v3 no servidor
 * ==============================================
 */
if (empty($captcha)) {
    header('Location: /index.php?e=captcha_empty');
    exit;
}

$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
$response = file_get_contents($recaptcha_url . '?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $captcha);
$captcha_data = json_decode($response, true);

// Se não houver sucesso ou o score for muito baixo, bloqueia o login
if (empty($captcha_data['success']) || ($captcha_data['score'] ?? 0) < 0.5) {
    error_log("reCAPTCHA falhou: score=" . ($captcha_data['score'] ?? 'null'));
    header('Location: /index.php?e=captcha');
    exit;
}

/**
 * ==============================================
 * 2️⃣  Monta o payload para sua API de autenticação
 * ==============================================
 */
$payload = [
    'login' => $login,                  // ou 'email', conforme sua API
    'senha' => $senha,                  // ou 'password'
    'g-recaptcha-response' => $captcha, // opcional — se sua API também valida
];

/**
 * ==============================================
 * 3️⃣  Chamada da API
 * ==============================================
 */
$r = http_post_form(AUTH_API_LOGIN, $payload);

/* fallback em JSON se necessário:
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

/**
 * ==============================================
 * 4️⃣  Tratamento de resposta da API
 * ==============================================
 */
if (!$r || ($r['status'] ?? 0) === 0 || ($r['body'] ?? '') === '' || ($r['status'] ?? 0) >= 500) {
    error_log("AUTH_API erro rede/5xx status=" . ($r['status'] ?? 'null'));
    header('Location: /index.php?e=api');
    exit;
}

if (($r['status'] ?? 0) === 401 || ($r['status'] ?? 0) === 403) {
    error_log("AUTH_API 401/403 body=" . substr($r['body'], 0, 400));
    header('Location: /index.php?e=cred');
    exit;
}

$j = json_decode($r['body'], true);
if (!is_array($j) || empty($j['ok']) || empty($j['token'])) {
    error_log("AUTH_API sem ok/token body=" . substr($r['body'], 0, 400));
    header('Location: /index.php?e=cred');
    exit;
}

/**
 * ==============================================
 * 5️⃣  Define o cookie JWT (AUTH_COOKIE)
 * ==============================================
 */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
$cookieOptions = [
    'expires'  => time() + 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $isHttps,
];
setcookie(AUTH_COOKIE, $j['token'], $cookieOptions);

error_log("AUTH_COOKIE setado (secure=" . ($cookieOptions['secure'] ? '1' : '0') . ") host=" . ($_SERVER['HTTP_HOST'] ?? ''));

/**
 * ==============================================
 * 6️⃣  Redireciona para a próxima página
 * ==============================================
 */
header('Location: ' . ($next ?: '/'));
exit;
