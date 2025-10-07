<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // === Debug opcional ===
    $logFile = '/tmp/debug_adubacao_organica.txt';
    file_put_contents($logFile, "=== NOVA REQUISIÇÃO " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
    file_put_contents($logFile, print_r($_POST, true), FILE_APPEND);

    // === Valida usuário (JWT ou sessão) ===
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'unauthorized']);
        exit;
    }

    // === Campos obrigatórios ===
    $data = $_POST['data'] ?? null;
    $areas = $_POST['area'] ?? [];
    $produtos = $_POST['produto'] ?? [];
    $tipo = trim($_POST['tipo'] ?? '');
    $quantidade = trim($_POST['quantidade'] ?? '');
    $forma_aplicacao = $_POST['forma_aplicacao'] ?? '';
    $n_referencia = trim($_POST['n_referencia'] ?? '');
    $obs = trim($_POST['obs'] ?? '');

    if (!$data || empty($areas) || empty($produtos) || !$tipo || !$quantidade) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'err' => 'missing_fields']);
        exit;
    }

    // === Buscar propriedade ativa ===
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) {
        echo json_encode(['ok' => false, 'err' => 'no_property']);
        exit;
    }

    $propriedade_id = $prop['id'];

    // === Inserir registro em apontamentos ===
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (user_id, propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, ?, 'adubacao_organica', ?, ?, ?, 'registro')
    ");
    $stmt->bind_param("iisss", $user_id, $propriedade_id, $data, $quantidade, $obs);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar apontamento: " . $stmt->error);
    }
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // === Função helper ===
    function salva_detalhe($apontamento_id, $campo, $valor) {
        global $mysqli;
        if ($valor === '' || $valor === null) return;
        $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();
        $stmt->close();
    }

    // === Salvar múltiplas áreas ===
    foreach ($areas as $area_id) {
        salva_detalhe($apontamento_id, 'area_id', $area_id);
    }

    // === Salvar múltiplos produtos ===
    foreach ($produtos as $produto_id) {
        salva_detalhe($apontamento_id, 'produto', $produto_id);
    }

    // === Salvar demais campos ===
    salva_detalhe($apontamento_id, 'tipo', $tipo);
    salva_detalhe($apontamento_id, 'forma_aplicacao', $forma_aplicacao);
    salva_detalhe($apontamento_id, 'n_referencia', $n_referencia);

    // === Log final ===
    file_put_contents($logFile, "Salvo com sucesso ID $apontamento_id\n\n", FILE_APPEND);

    echo json_encode([
        'ok' => true,
        'msg' => 'Adubação orgânica registrada com sucesso!',
        'id' => $apontamento_id
    ]);
}
catch (Exception $e) {
    file_put_contents('/tmp/debug_adubacao_organica.txt', "ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'exception', 'msg' => $e->getMessage()]);
}
