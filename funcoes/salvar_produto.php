<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'err'=>'method_not_allowed']);
    exit;
}

try {
    // valida JWT
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'err'=>'unauthorized']);
        exit;
    }

    // pega dados do formulário
    $nome = trim($_POST['pnome'] ?? '');
    $tipo = $_POST['ptipo'] ?? '';
    $atr  = $_POST['patr'] ?? '';

    if ($nome === '' || $tipo === '' || $atr === '') {
        echo json_encode(['ok'=>false,'err'=>'missing_fields']);
        exit;
    }

    // mapear valores
    $mapTipo = ['1'=>'convencional','2'=>'organico','3'=>'integrado'];
    $mapAtr  = ['hidro'=>'hidro','semi-hidro'=>'semi-hidro','solo'=>'solo'];

    $tipoVal = $mapTipo[$tipo] ?? null;
    $atrVal  = $mapAtr[$atr] ?? null;

    if (!$tipoVal || !$atrVal) {
        echo json_encode(['ok'=>false,'err'=>'invalid_values']);
        exit;
    }

    // insere produto
    $stmt = $mysqli->prepare("
        INSERT INTO produtos (user_id, nome, tipo, atributo, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isss", $user_id, $nome, $tipoVal, $atrVal);
    $stmt->execute();

    $newId = $stmt->insert_id;

    // decide se retorna JSON ou redireciona
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($accept, 'application/json') || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['ok'=>true,'id'=>$newId]);
    } else {
        header("Location: /home/produtos.php?sucesso=1");
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'err'=>'db','msg'=>$e->getMessage()]);
}
