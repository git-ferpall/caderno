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

    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) throw new Exception("Usuário não autenticado");

    // Busca propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();
    if (!$prop) throw new Exception("Nenhuma propriedade ativa encontrada");

    $propriedade_id = $prop['id'];

    // Dados do formulário
    $data        = $_POST['data'] ?? null;
    $fontes      = $_POST['fonte'] ?? [];
    $volume      = $_POST['volume'] ?? null;
    $finalidade  = $_POST['finalidade'] ?? null;
    $obs         = $_POST['obs'] ?? null;

    if (!is_array($fontes)) $fontes = [$fontes];

    if (!$data || !$volume || empty($fontes)) {
        throw new Exception("Campos obrigatórios ausentes");
    }

    $mysqli->begin_transaction();

    // === apontamentos (principal)
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'controle_agua', ?, ?, ?, 'pendente')
    ");
    $stmt->bind_param("isss", $propriedade_id, $data, $volume, $obs);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // === detalhes (fontes + finalidade)
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");

    foreach ($fontes as $fonte) {
        $campo = "fonte";
        $valor = trim($fonte);
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();
    }

    if (!empty($finalidade)) {
        $campo = "finalidade";
        $stmt->bind_param("iss", $apontamento_id, $campo, $finalidade);
        $stmt->execute();
    }

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Controle de água registrado com sucesso!']);

} catch (Exception $e) {
    if (isset($mysqli)) $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'exception', 'msg' => $e->getMessage()]);
}
