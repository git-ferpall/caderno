<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/http.php';

$login = trim($_POST['login'] ?? '');
$senha = trim($_POST['senha'] ?? '');
$next  = $_POST['next'] ?? '/';

if ($login === '' || $senha === '') { 
    header('Location: /index.php?e=1'); 
    exit; 
}

$r = http_post_form(AUTH_API_LOGIN, ['login'=>$login,'senha'=>$senha]);

// Falha de rede/timeout/corpo vazio/5xx → erro de API
if (!$r || $r['status'] === 0 || $r['body'] === '' || $r['status'] >= 500) {
    header('Location: /index.php?e=api'); 
    exit;
}

// 401/403 → credenciais inválidas
if ($r['status'] === 401 || $r['status'] === 403) {
    header('Location: /index.php?e=cred'); 
    exit;
}

// Demais casos → parse JSON e exige token
$j = json_decode($r['body'], true);
if (!is_array($j) || empty($j['ok']) || empty($j['token'])) {
    header('Location: /index.php?e=cred'); 
    exit;
}

/**
 * Configuração dinâmica do cookie
 */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
           || $_SERVER['SERVER_PORT'] == 443;

$cookieOptions = [
    'expires'  => time() + 3600,
    'path'     => '/',
    'httponly' => true,
];

// Em produção (HTTPS)
if ($isHttps) {
    $cookieOptions['secure'] = true;
    $cookieOptions['samesite'] = 'Lax';   // pode ser 'None' se precisar cross-domain
    // melhor não setar domain manualmente → PHP usa o host atual
} else {
    // Em dev/local (HTTP), não dá para usar Secure
    $cookieOptions['secure'] = false;
    $cookieOptions['samesite'] = 'Lax';
}

// Grava cookie
setcookie(AUTH_COOKIE, $j['token'], $cookieOptions);

// Debug opcional no log
if (!headers_sent()) {
    error_log("AUTH_COOKIE setado com sucesso para host: " . ($_SERVER['HTTP_HOST'] ?? ''));
} else {
    error_log("ERRO: headers já enviados, não foi possível setar AUTH_COOKIE");
}

// Redireciona para a página desejada
header('Location: ' . ($next ?: '/')); 
exit;
