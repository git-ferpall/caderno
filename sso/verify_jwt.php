<?php
// /var/www/html/sso/verify_jwt.php

require_once __DIR__ . '/../configuracao/env.php'; // contém JWT_SECRET

function b64url_decode($d) { 
    return base64_decode(strtr($d, '-_', '+/')); 
}

/**
 * 🔒 Verifica o JWT e retorna o payload decodificado
 * (sem validar permissões de acesso)
 */
function verify_jwt() {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $jwt = null;

    // 🔐 Prioridade 1: Header Bearer
    if (preg_match('/Bearer\s+(.+)/', $auth, $m)) {
        $jwt = $m[1];
    }

    // 🔐 Prioridade 2: Cookie AUTH_COOKIE (SSO Frutag)
    elseif (!empty($_COOKIE['AUTH_COOKIE'])) {
        $jwt = $_COOKIE['AUTH_COOKIE'];
    }

    // 🔐 Prioridade 3: Cookie antigo "token"
    elseif (!empty($_COOKIE['token'])) {
        $jwt = $_COOKIE['token'];
    }


    if (!$jwt) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'no_token']);
        exit;
    }

    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'bad_token']);
        exit;
    }

    [$h64, $p64, $s64] = $parts;
    $payload = json_decode(b64url_decode($p64), true);

    if (!$payload) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'bad_payload']);
        exit;
    }

    // ✅ Valida assinatura
    $sign = hash_hmac('sha256', "$h64.$p64", JWT_SECRET, true);
    if (!hash_equals($sign, b64url_decode($s64))) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'sig']);
        exit;
    }

    // ⏰ Valida expiração
    if (!empty($payload['exp']) && $payload['exp'] < time()) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'exp']);
        exit;
    }

    return $payload;
}

/**
 * 🧠 Valida o JWT e garante que o usuário tenha acesso ao módulo Caderno de Campo
 */
function verify_jwt_and_access($mysqli) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'invalid_payload']);
        exit;
    }

    // 🔍 Verifica no banco se o usuário está ativo e tem acesso ao Caderno de Campo
    $stmt = $mysqli->prepare("SELECT ativo, caderno_campo FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'err' => 'user_not_found']);
        exit;
    }

    if ($user['ativo'] !== 'S') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'err' => 'user_inactive']);
        exit;
    }

    if ($user['caderno_campo'] !== 'S') {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'err' => 'access_denied',
            'msg' => 'Usuário sem permissão para acessar o Caderno de Campo'
        ]);
        exit;
    }

    // ✅ Tudo certo
    return $payload;
}
