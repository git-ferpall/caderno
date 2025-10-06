<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

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

    // Busca propriedade ativa
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
    $produto          = $_POST['produto'] ?? null;
    $tipo             = $_POST['tipo'] ?? null;
    $quantidade       = $_POST['quantidade'] ?? null;
    $prnt             = $_POST['prnt'] ?? null;
    $forma_aplicacao  = $_POST['forma_aplicacao'] ?? null;
    $n_referencia     = $_POST['n_referencia'] ?? null;
    $obs              = $_POST['obs'] ?? null;

    if (!$data || !$produto || !$tipo || !$quantidade || empty($areas)) {
        throw new Exception("Campos obrigatórios ausentes");
    }

    // Inicia transação
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

    // Inserir detalhes do apontamento
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");

    // Áreas
    foreach ($areas as $area_id) {
        $campo = "area_id";
        $valor = (string)(int)$area_id;
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();
    }

    // Demais detalhes
    $detalhes = [
        'produto'         => $produto,
        'tipo'            => $tipo,
        'quantidade'      => $quantidade,
        'prnt'            => $prnt,
        'forma_aplicacao' => $forma_aplicacao,
        'n_referencia'    => $n_referencia,
        'obs'             => $obs
    ];

    foreach ($detalhes as $campo => $valor) {
        if ($valor !== null && $valor !== '') {
            $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
            $stmt->execute();
        }
    }

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Adubação registrada com sucesso!']);

} catch (Exception $e) {
    if ($mysqli->errno) {
        $mysqli->rollback();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'exception', 'msg' => $e->getMessage()]);
}
