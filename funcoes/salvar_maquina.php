<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'err'=>'method_not_allowed']);
    exit;
}

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'err'=>'unauthorized']);
        exit;
    }

    $id    = intval($_POST['id'] ?? 0);
    $nome  = trim($_POST['mnome'] ?? '');
    $marca = trim($_POST['mmarca'] ?? '');
    $tipo  = $_POST['mtipo'] ?? '';

    if ($nome === '' || $marca === '' || $tipo === '') {
        echo json_encode(["ok" => false, "error" => "Dados incompletos"]);
        exit;
    }

    // Mapear tipo
    $mapTipo = ['1'=>'motorizado','2'=>'acoplado','3'=>'manual'];
    $tipoVal = $mapTipo[$tipo] ?? null;

    if (!$tipoVal) {
        echo json_encode(["ok" => false, "error" => "Tipo inválido"]);
        exit;
    }

    if ($id > 0) {
        $stmt = $mysqli->prepare("UPDATE maquinas SET nome=?, marca=?, tipo=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sssii", $nome, $marca, $tipoVal, $id, $user_id);
        $action = "update";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO maquinas (user_id, nome, marca, tipo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $nome, $marca, $tipoVal);
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
