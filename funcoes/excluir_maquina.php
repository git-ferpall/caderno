<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }

    if (!$user_id) {
        echo json_encode(["ok" => false, "error" => "Usuário não autenticado"]);
        exit;
    }

    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(["ok" => false, "error" => "ID inválido"]);
        exit;
    }

    $stmt = $mysqli->prepare("DELETE FROM maquinas WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["ok" => true]);
    } else {
        echo json_encode(["ok" => false, "error" => "Máquina não encontrada ou sem permissão"]);
    }

    $stmt->close();

} catch (Throwable $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
