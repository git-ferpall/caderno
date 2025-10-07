<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// Aceita apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'err' => 'method_not_allowed']);
    exit;
}

try {
    session_start();

    // Identifica o usuário
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) {
        echo json_encode(['ok' => false, 'err' => 'unauthorized']);
        exit;
    }

    // Obtém a propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) {
        echo json_encode(['ok' => false, 'err' => 'no_active_property']);
        exit;
    }
    $propriedade_id = $prop['id'];

    // Dados recebidos do formulário
    $data             = $_POST['data'] ?? null;
    $areas            = $_POST['area'] ?? [];
    $produtos         = $_POST['produto'] ?? [];
    $tempo_irrigacao  = $_POST['tempo_irrigacao'] ?? null;
    $volume_aplicado  = $_POST['volume_aplicado'] ?? null;
    $obs              = $_POST['obs'] ?? null;

    if (!is_array($areas)) $areas = [$areas];
    if (!is_array($produtos)) $produtos = [$produtos];

    // Validação básica
    if (!$data || empty($areas) || empty($produtos) || !$volume_aplicado) {
        throw new Exception("Campos obrigatórios ausentes");
    }

    // Inicia transação
    $mysqli->begin_transaction();

    // === Inserir registro principal (apontamentos)
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'irrigacao', ?, ?, ?, 'pendente')
    ");
    $stmt->bind_param("isss", $propriedade_id, $data, $volume_aplicado, $obs);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // === Inserir detalhes (áreas, produtos, tempo)
    $stmt = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, ?, ?)
    ");

    // Áreas
    foreach ($areas as $area_id) {
        $campo = "area_id";
        $valor = (string)(int)$area_id;
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();
    }

    // Produtos
    foreach ($produtos as $produto_id) {
        $campo = "produto";
        $valor = (string)(int)$produto_id;
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();
    }

    // Tempo de irrigação (vai em detalhes)
    if (!empty($tempo_irrigacao)) {
        $campo = "tempo_irrigacao";
        $valor = (string)$tempo_irrigacao;
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();
    }

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Irrigação registrada com sucesso!']);

} catch (Exception $e) {
    if (isset($mysqli)) {
        $mysqli->rollback();
    }
    http_response_code(500);
    echo json_encode([
        'ok'  => false,
        'err' => 'exception',
        'msg' => $e->getMessage()
    ]);
}
