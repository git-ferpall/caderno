<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

try {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) throw new Exception('Usuário não autenticado');

    file_put_contents(__DIR__ . "/debug_fertilizante.txt", print_r($_POST, true) . "\n---\n", FILE_APPEND);

    // === Propriedade ativa ===
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $prop = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$prop) throw new Exception('Nenhuma propriedade ativa encontrada');
    $propriedade_id = $prop['id'];

    // === Dados do formulário ===
    $estufa_id  = $_POST['estufa_id'] ?? null;
    $area_id    = $_POST['area_id'] ?? null;
    $produto_id = $_POST['produto_id'] ?? null;
    $dose       = trim($_POST['dose'] ?? '');
    $tipo       = trim($_POST['tipo'] ?? '');
    $obs        = trim($_POST['obs'] ?? '');
    $data       = date('Y-m-d');

    if (!$area_id || !$produto_id) {
        throw new Exception("Campos obrigatórios não informados (area_id, produto_id)");
    }

    // === Transação ===
    $mysqli->begin_transaction();

    $tipo_apontamento = "fertilizante";
    $status = "concluido"; // já finalizado
    $quantidade = ($dose !== '') ? floatval($dose) : 0.0;
    $data_conclusao = $data;

    // 🧩 Inserir apontamento principal
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status, data_conclusao)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdsss", $propriedade_id, $tipo_apontamento, $data, $quantidade, $obs, $status, $data_conclusao);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    if (!$apontamento_id) throw new Exception("Falha ao criar apontamento principal");

    // 🧩 Detalhes: área_id (numérico)
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, 'area_id', ?)");
    $stmt->bind_param("is", $apontamento_id, $area_id);
    $stmt->execute();
    $stmt->close();

    // 🧩 Detalhes: produto_id
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, 'produto_id', ?)");
    $stmt->bind_param("is", $apontamento_id, $produto_id);
    $stmt->execute();
    $stmt->close();

    // 🧩 Detalhes: tipo_aplicacao
    $tipo_txt = ($tipo == 1) ? "Foliar" : "Solução";
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, 'tipo_aplicacao', ?)");
    $stmt->bind_param("is", $apontamento_id, $tipo_txt);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    if ($mysqli->errno) $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
