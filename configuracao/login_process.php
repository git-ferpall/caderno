<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/recaptcha.php'; // 🔒 chaves do Google

session_start();

/**
 * ==================================================
 * Função auxiliar: salva mensagem de erro na sessão
 * ==================================================
 */
function setLoginError($mensagem) {
    $_SESSION['retorno'] = [
        'mensagem' => $mensagem,
        'hora' => date('H:i:s')
    ];
}

$login   = trim($_POST['login'] ?? '');
$senha   = trim($_POST['senha'] ?? '');
$next    = $_POST['next'] ?? '/';
$captcha = trim($_POST['g-recaptcha-response'] ?? ''); // token reCAPTCHA

if ($login === '' || $senha === '') {
    setLoginError('Por favor, preencha usuário e senha.');
    header('Location: /index.php');
    exit;
}

/**
 * ==================================================
 * 1️⃣  Verifica se o token chegou
 * ==================================================
 */
if (empty($captcha)) {
    error_log("reCAPTCHA token vazio");
    setLoginError('Validação de segurança falhou. Recarregue a página e tente novamente.');
    header('Location: /index.php');
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
    setLoginError('Erro ao validar o reCAPTCHA. Tente novamente.');
    header('Location: /index.php');
    exit;
}

$captcha_data = json_decode($response, true);
file_put_contents('/tmp/debug_recaptcha.log', date('[Y-m-d H:i:s] ') . "JSON_DECODED: " . json_encode($captcha_data) . "\n", FILE_APPEND);

// se não houver sucesso ou score muito baixo, bloqueia
$score = $captcha_data['score'] ?? 0;
if (empty($captcha_data['success']) || $score < 0.2) {
    error_log("reCAPTCHA falhou: score=" . ($score ?: 'null'));
    setLoginError('Falha na validação de segurança. Tente novamente.');
    header('Location: /index.php');
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
    setLoginError('Erro de comunicação com o servidor. Tente novamente mais tarde.');
    header('Location: /index.php');
    exit;
}

/**
 * ==================================================
 * 5️⃣  Trata erros de autenticação
 * ==================================================
 */
if (($r['status'] ?? 0) === 401) {
    error_log("AUTH_API 401 body=" . substr($r['body'], 0, 400));
    setLoginError('Usuário ou senha incorretos.');
    header('Location: /index.php');
    exit;
}

if (($r['status'] ?? 0) === 403) {
    error_log("AUTH_API 403 body=" . substr($r['body'], 0, 400));
    setLoginError('Usuário sem permissão para acessar o Caderno de Campo.');
    header('Location: /index.php');
    exit;
}

$j = json_decode($r['body'], true);
if (!is_array($j) || empty($j['ok']) || empty($j['token'])) {
    error_log("AUTH_API sem ok/token body=" . substr($r['body'], 0, 400));
    setLoginError('Falha na autenticação. Verifique suas credenciais.');
    header('Location: /index.php');
    exit;
}

/**
 * ==================================================
 * 6️⃣  Define o cookie JWT (AUTH_COOKIE)
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
 * 7️⃣  Redireciona para a próxima página
 * ==================================================
 */
header('Location: ' . ($next ?: '/'));
exit;
