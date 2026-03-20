<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
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

    // Usuário
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

    // Dados
    $data             = $_POST['data'] ?? null;
    $areas            = $_POST['area'] ?? [];
    $produtos         = $_POST['produto'] ?? [];
    $tempo_irrigacao  = $_POST['tempo_irrigacao'] ?? null;
    $unidade_tempo    = $_POST['unidade_tempo'] ?? null;
    $volume_aplicado  = $_POST['volume_aplicado'] ?? null;
    $unidade_volume   = $_POST['unidade_volume'] ?? null;
    $obs              = $_POST['obs'] ?? null;

    if (!is_array($areas)) $areas = [$areas];
    if (!is_array($produtos)) $produtos = [$produtos];

    if (!$data || empty($areas) || empty($produtos) || !$volume_aplicado || !$unidade_volume) {
        throw new Exception("Campos obrigatórios ausentes");
    }

    $dataAtual = date('Y-m-d');
    $status = ($data < $dataAtual) ? 'concluido' : 'pendente';

    $mysqli->begin_transaction();

    // PRINCIPAL (quantidade = volume)
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, unidade, observacoes, status)
        VALUES (?, 'irrigacao', ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("isdsss", 
        $propriedade_id, 
        $data, 
        $volume_aplicado, 
        $unidade_volume, 
        $obs,
        $status
    );
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // DETALHES
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

    // TEMPO
    if (!empty($tempo_irrigacao)) {

        // valor
        $campo = "tempo_irrigacao";
        $valor = (string)$tempo_irrigacao;
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();

        // unidade
        if (!empty($unidade_tempo)) {
            $campo = "tempo_unidade";
            $valor = $unidade_tempo;
            $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
            $stmt->execute();
        }
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