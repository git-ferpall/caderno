<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/recaptcha.php'; // 🔒 chaves do Google

session_start();

$login   = trim($_POST['login'] ?? '');
$senha   = trim($_POST['senha'] ?? '');
$next    = $_POST['next'] ?? '/';
$captcha = trim($_POST['g-recaptcha-response'] ?? ''); // token reCAPTCHA

if ($login === '' || $senha === '') {
    header('Location: /index.php?e=1');
    exit;
}

/**
 * ==================================================
 * 1️⃣  Verifica se o token chegou
 * ==================================================
 */
if (empty($captcha)) {
    error_log("reCAPTCHA token vazio");
    header('Location: /index.php?e=captcha_empty');
    exit;
}

/**
 * ==================================================
 * 2️⃣  Validação reCAPTCHA v3 (via cURL)
 * ==================================================
 */
$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';

$ch = curl_init($recaptcha_url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $captcha,
        'remoteip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

// cria log dedicado
file_put_contents('/tmp/debug_recaptcha.log', date('[Y-m-d H:i:s] ') . "RAW_RESPONSE: $response | ERROR: $error\n", FILE_APPEND);

if (!$response) {
    error_log("reCAPTCHA erro cURL: $error");
    header('Location: /index.php?e=captcha');
    exit;
}

$captcha_data = json_decode($response, true);
file_put_contents('/tmp/debug_recaptcha.log', date('[Y-m-d H:i:s] ') . "JSON_DECODED: " . json_encode($captcha_data) . "\n", FILE_APPEND);

// se não houver sucesso ou score muito baixo, bloqueia
$score = $captcha_data['score'] ?? 0;
if (empty($captcha_data['success']) || $score < 0.2) {
    error_log("reCAPTCHA falhou: score=" . ($score ?: 'null'));
    header('Location: /index.php?e=captcha');
    exit;
}

/**
 * ==================================================
 * 3️⃣  Monta o payload da API de autenticação
 * ==================================================
 */
$payload = [
    'login' => $login,
    'senha' => $senha,
    'g-recaptcha-response' => $captcha,
];

/**
 * ==================================================
 * 4️⃣  Chamada da API (via função http_post_form)
 * ==================================================
 */
$r = http_post_form(AUTH_API_LOGIN, $payload);

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
 * ==================================================
 * 5️⃣  Define o cookie JWT (AUTH_COOKIE)
 * ==================================================
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
 * ==================================================
 * 6️⃣  Redireciona para a próxima página
 * ==================================================
 */
header('Location: ' . ($next ?: '/'));
exit;
