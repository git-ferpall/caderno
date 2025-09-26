<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'err' => 'method_not_allowed']);
    exit;
}

try {
    // valida JWT
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'unauthorized']);
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

    // 🔹 Buscar propriedade ativa do usuário
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) {
        echo json_encode(["ok" => false, "error" => "Nenhuma propriedade ativa encontrada"]);
        exit;
    }
    $propriedade_id = $prop['id'];

    if ($id > 0) {
        // UPDATE
        $stmt = $mysqli->prepare("UPDATE maquinas SET nome=?, marca=?, tipo=? WHERE id=? AND user_id=? AND propriedade_id=?");
        $stmt->bind_param("sssiii", $nome, $marca, $tipo, $id, $user_id, $propriedade_id);
        $action = "update";
    } else {
        // INSERT
        $stmt = $mysqli->prepare("INSERT INTO maquinas (user_id, propriedade_id, nome, marca, tipo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $user_id, $propriedade_id, $nome, $marca, $tipo);
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
    echo json_encode(['ok' => false, 'err' => 'db', 'msg' => $e->getMessage()]);
}
