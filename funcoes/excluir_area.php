<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'err' => 'method_not_allowed']);
    exit;
}

try {
    // 🔐 Valida JWT
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'unauthorized']);
        exit;
    }

    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['ok' => false, 'err' => 'invalid_id']);
        exit;
    }

    // 🔎 Verifica se a área pertence ao usuário antes de excluir
    $stmtCheck = $mysqli->prepare("SELECT id FROM areas WHERE id = ? AND user_id = ?");
    $stmtCheck->bind_param("ii", $id, $user_id);
    $stmtCheck->execute();
    $res = $stmtCheck->get_result();
    $area = $res->fetch_assoc();
    $stmtCheck->close();

    if (!$area) {
        echo json_encode(['ok' => false, 'err' => 'area_not_found']);
        exit;
    }

    // 🔄 Inicia transação
    $mysqli->begin_transaction();

    // 💣 1️⃣ Exclui a área (bancadas vinculadas serão excluídas automaticamente via ON DELETE CASCADE)
    $stmt = $mysqli->prepare("DELETE FROM areas WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $linhas = $stmt->affected_rows;
    $stmt->close();

    if ($linhas > 0) {
        $mysqli->commit();
        echo json_encode(['ok' => true, 'msg' => 'Área e bancadas associadas excluídas com sucesso']);
    } else {
        $mysqli->rollback();
        echo json_encode(['ok' => false, 'err' => 'not_found_or_not_owner']);
    }

} catch (Exception $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'db_error', 'msg' => $e->getMessage()]);
}
