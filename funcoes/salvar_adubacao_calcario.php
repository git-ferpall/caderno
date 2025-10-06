<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'err' => 'method_not_allowed']);
    exit;
}

try {
    session_start();

    // Identifica usuário
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) {
        echo json_encode(['ok' => false, 'err' => 'unauthorized']);
        exit;
    }

    // Propriedade ativa
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

    // Dados do formulário
    $data             = $_POST['data'] ?? null;
    $areas            = $_POST['area'] ?? [];
    $produtos         = $_POST['produto'] ?? [];
    if (!is_array($produtos)) {
        $produtos = [$produtos];
    }
    $tipo             = $_POST['tipo'] ?? null;
    $quantidade       = $_POST['quantidade'] ?? null;
    $prnt             = $_POST['prnt'] ?? null;
    $forma_aplicacao  = $_POST['forma_aplicacao'] ?? null;
    $n_referencia     = $_POST['n_referencia'] ?? null;
    $obs              = $_POST['obs'] ?? null;

    // Validação básica
    if (!$data || !$tipo || !$quantidade || empty($areas) || empty($produtos)) {
        throw new Exception("Campos obrigatórios ausentes");
    }

    // Transação
    $mysqli->begin_transaction();

    // Inserir apontamento principal
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'adubacao_calcario', ?, ?, ?, 'pendente')
    ");
    $stmt->bind_param("isss", $propriedade_id, $data, $quantidade, $obs);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // Inserir detalhes
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

    // Produtos (múltiplos)
    foreach ($produtos as $produto_id) {
        $campo = "produto";
        $valor = (string)(int)$produto_id;
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();
    }

    // Demais campos (não duplicar quantidade/obs)
    $detalhes = [
        'tipo'            => $tipo,
        'prnt'            => (string)$prnt,
        'forma_aplicacao' => $forma_aplicacao ?? '',
        'n_referencia'    => $n_referencia ?? ''
    ];

    foreach ($detalhes as $campo => $valor) {
        $valor = (string)$valor;
        if (trim($valor) !== '') {
            $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
            $stmt->execute();
        }
    }

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Adubação registrada com sucesso!']);

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
