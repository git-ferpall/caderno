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

    // 🔎 Busca o tipo e nome da área antes de excluir
    $stmtCheck = $mysqli->prepare("SELECT nome, tipo FROM areas WHERE id = ? AND user_id = ?");
    $stmtCheck->bind_param("ii", $id, $user_id);
    $stmtCheck->execute();
    $res = $stmtCheck->get_result();
    $area = $res->fetch_assoc();
    $stmtCheck->close();

    if (!$area) {
        echo json_encode(['ok' => false, 'err' => 'area_not_found']);
        exit;
    }

    $tipo = $area['tipo'];
    $nome = $area['nome'];

    // 🔄 Inicia transação
    $mysqli->begin_transaction();

    // 🧱 1️⃣ Se for bancada, apaga também da tabela "bancadas"
    if ($tipo === 'bancada') {
        $stmtB = $mysqli->prepare("DELETE FROM bancadas WHERE nome = ?");
        $stmtB->bind_param("s", $nome);
        $stmtB->execute();
        $stmtB->close();
    }

    // 🧱 2️⃣ Apaga a área da tabela "areas"
    $stmtA = $mysqli->prepare("DELETE FROM areas WHERE id = ? AND user_id = ?");
    $stmtA->bind_param("ii", $id, $user_id);
    $stmtA->execute();
    $linhas = $stmtA->affected_rows;
    $stmtA->close();

    if ($linhas > 0) {
        $mysqli->commit();
        echo json_encode(['ok' => true, 'msg' => 'Área (e bancada, se aplicável) excluída com sucesso']);
    } else {
        $mysqli->rollback();
        echo json_encode(['ok' => false, 'err' => 'not_found_or_not_owner']);
    }

} catch (Exception $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'db_error', 'msg' => $e->getMessage()]);
}
