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
    // valida JWT ou sessão
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'err'=>'unauthorized']);
        exit;
    }

    // pega dados
    $id   = intval($_POST['id'] ?? 0);
    $nome = trim($_POST['pnome'] ?? '');
    $tipo = $_POST['ptipo'] ?? '';
    $atr  = $_POST['patr'] ?? '';

    if ($nome === '' || $tipo === '' || $atr === '') {
        echo json_encode(["ok" => false, "error" => "Dados incompletos"]);
        exit;
    }

    // mapear valores
    $mapTipo = ['1'=>'convencional','2'=>'organico','3'=>'integrado'];
    $mapAtr  = ['hidro'=>'hidro','semi-hidro'=>'semi-hidro','solo'=>'solo'];

    $tipoVal = $mapTipo[$tipo] ?? null;
    $atrVal  = $mapAtr[$atr] ?? null;

    if (!$tipoVal || !$atrVal) {
        echo json_encode(["ok" => false, "error" => "Valores inválidos"]);
        exit;
    }

    if ($id > 0) {
        // UPDATE
        $stmt = $mysqli->prepare("UPDATE produtos SET nome=?, tipo=?, atributo=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sssii", $nome, $tipoVal, $atrVal, $id, $user_id);
        $action = "update";
    } else {
        // INSERT
        $stmt = $mysqli->prepare("INSERT INTO produtos (user_id, nome, tipo, atributo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $nome, $tipoVal, $atrVal);
        $action = "insert";
    }

    if ($stmt->execute()) {
        echo json_encode([
            "ok" => true,
            "id" => $id > 0 ? $id : $stmt->insert_id,
            "action" => $action
        ]);
    } else {
        echo json_encode(["ok" => false, "error" => $stmt->error]);
    }

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'err'=>'db','msg'=>$e->getMessage()]);
}
