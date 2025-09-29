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

    // propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id=? AND ativo=1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) {
        echo json_encode(['ok'=>false,'error'=>'Nenhuma propriedade ativa encontrada']);
        exit;
    }
    $prop_id = $prop['id'];

    $id   = intval($_POST['id'] ?? 0);
    $nome = trim($_POST['anome'] ?? '');
    $tipo = $_POST['atipo'] ?? '';

    $mapTipo = ['1'=>'estufa','2'=>'solo','3'=>'outro'];
    $tipoVal = $mapTipo[$tipo] ?? null;

    if ($nome === '' || !$tipoVal) {
        echo json_encode(["ok"=>false,"error"=>"Dados inválidos"]);
        exit;
    }

    if ($id > 0) {
        $stmt = $mysqli->prepare("UPDATE areas SET nome=?, tipo=? WHERE id=? AND user_id=? AND propriedade_id=?");
        $stmt->bind_param("ssiii", $nome, $tipoVal, $id, $user_id, $prop_id);
        $action = "update";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO areas (user_id, propriedade_id, nome, tipo) VALUES (?,?,?,?,?)");
        $stmt->bind_param("iiss", $user_id, $prop_id, $nome, $tipoVal);
        $action = "insert";
    }

    if ($stmt->execute()) {
        echo json_encode([
            "ok"=>true,
            "id"=>$id > 0 ? $id : $stmt->insert_id,
            "action"=>$action
        ]);
    } else {
        echo json_encode(["ok"=>false,"error"=>$stmt->error]);
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'err'=>'db','msg'=>$e->getMessage()]);
}
