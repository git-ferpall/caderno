<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

try {
    // === Identifica o usuário logado ===
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }

    if (!$user_id) {
        throw new Exception('Usuário não autenticado');
    }

    // === Descobre propriedade ativa ===
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) {
        throw new Exception('Nenhuma propriedade ativa encontrada');
    }

    $propriedade_id = $prop['id'];

    // === Dados recebidos do formulário ===
    $estufa_id   = (int)($_POST['estufa_id'] ?? 0);
    $area_id     = (int)($_POST['area_id'] ?? 0);
    $produto_id  = (int)($_POST['produto_id'] ?? 0);
    $dose        = trim($_POST['dose'] ?? '');
    $tipo        = trim($_POST['tipo'] ?? '');
    $obs         = trim($_POST['obs'] ?? '');
    $data        = date('Y-m-d');

    if ($estufa_id <= 0 || $area_id <= 0) {
        throw new Exception('Estufa ou área não identificada.');
    }

    if ($produto_id <= 0) {
        throw new Exception('Selecione o fertilizante.');
    }

    // === Transação para consistência ===
    $mysqli->begin_transaction();

    // 1️⃣ Inserir o apontamento principal
    $tipo_apontamento = "fertilizante";
    $status = "pendente";

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $quantidade = ($dose !== '') ? floatval($dose) : 0;
    $stmt->bind_param("issdss", $propriedade_id, $tipo_apontamento, $data, $quantidade, $obs, $status);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    if (!$apontamento_id) {
        throw new Exception('Erro ao criar apontamento.');
    }

    // 2️⃣ Inserir detalhes essenciais (sem observações duplicadas)
    $detalhes = [
        ['campo' => 'estufa_id',      'valor' => $estufa_id],
        ['campo' => 'area_id',        'valor' => $area_id],
        ['campo' => 'produto_id',     'valor' => $produto_id],
        ['campo' => 'tipo_aplicacao', 'valor' => ($tipo == 1 ? "Foliar" : "Solução")]
    ];

    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    foreach ($detalhes as $d) {
        $stmt->bind_param("iss", $apontamento_id, $d['campo'], $d['valor']);
        $stmt->execute();
    }
    $stmt->close();

    $mysqli->commit();

    echo json_encode([
        'ok' => true,
        'msg' => 'Fertilizante salvo com sucesso!'
    ]);

} catch (Exception $e) {
    if ($mysqli->errno) $mysqli->rollback();
    echo json_encode([
        'ok' => false,
        'err' => $e->getMessage()
    ]);
}
