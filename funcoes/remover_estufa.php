<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/hidroponia_helpers.php';

header('Content-Type: application/json');
session_start();

// 1️⃣ Autenticação
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// 2️⃣ Propriedade ativa
$stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$prop = $res->fetch_assoc();
$stmt->close();

if (!$prop) {
    echo json_encode(['ok' => false, 'err' => 'Nenhuma propriedade ativa']);
    exit;
}
$propriedade_id = (int) $prop['id'];

// 3️⃣ Dados recebidos
$estufa_id = (int) ($_POST['estufa_id'] ?? 0);
if ($estufa_id <= 0) {
    echo json_encode(['ok' => false, 'err' => 'ID da estufa não informado']);
    exit;
}

try {
    $mysqli->begin_transaction();

    // 4️⃣ Valida se a estufa pertence a esta propriedade
    $check = $mysqli->prepare("SELECT id FROM estufas WHERE id = ? AND propriedade_id = ? LIMIT 1");
    $check->bind_param("ii", $estufa_id, $propriedade_id);
    $check->execute();
    $res = $check->get_result();
    $estufa = $res->fetch_assoc();
    $check->close();

    if (!$estufa) {
        throw new Exception('Estufa não pertence à propriedade ativa.');
    }

    // 5️⃣ Coleta as áreas vinculadas às bancadas desta estufa
    $areas_ids = [];
    $stmtA = $mysqli->prepare("SELECT area_id FROM bancadas WHERE estufa_id = ? AND area_id IS NOT NULL");
    $stmtA->bind_param("i", $estufa_id);
    $stmtA->execute();
    $resA = $stmtA->get_result();
    while ($row = $resA->fetch_assoc()) {
        $aid = (int) $row['area_id'];
        if ($aid > 0) {
            $areas_ids[] = $aid;
        }
    }
    $stmtA->close();

    // 6️⃣ Remove os produtos vinculados às bancadas
    if (hidroponiaTabelaProdutosExiste($mysqli)) {
        $delProdutos = $mysqli->prepare("
            DELETE bp FROM bancada_produtos bp
            INNER JOIN bancadas b ON b.id = bp.bancada_id
            WHERE b.estufa_id = ?
        ");
        $delProdutos->bind_param("i", $estufa_id);
        $delProdutos->execute();
        $delProdutos->close();
    }

    // 7️⃣ Remove as bancadas
    $deleteBancadas = $mysqli->prepare("DELETE FROM bancadas WHERE estufa_id = ?");
    $deleteBancadas->bind_param("i", $estufa_id);
    $deleteBancadas->execute();
    $deleteBancadas->close();

    // 8️⃣ Remove as áreas que foram criadas junto com as bancadas
    if ($areas_ids) {
        $placeholders = implode(',', array_fill(0, count($areas_ids), '?'));
        $tipos = str_repeat('i', count($areas_ids) + 1);
        $params = array_merge($areas_ids, [$propriedade_id]);

        $delAreas = $mysqli->prepare("DELETE FROM areas WHERE id IN ($placeholders) AND propriedade_id = ?");
        $delAreas->bind_param($tipos, ...$params);
        $delAreas->execute();
        $delAreas->close();
    }

    // 9️⃣ Remove a estufa
    $stmt = $mysqli->prepare("DELETE FROM estufas WHERE id = ? AND propriedade_id = ?");
    $stmt->bind_param("ii", $estufa_id, $propriedade_id);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Estufa e todas as bancadas foram removidas com sucesso!']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => caderno_erro_msg($e)]);
}
