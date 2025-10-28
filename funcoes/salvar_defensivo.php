<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

try {
    // Identifica o usuário
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) throw new Exception("Usuário não autenticado");

    // Propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) throw new Exception("Nenhuma propriedade ativa encontrada");
    $propriedade_id = $prop['id'];

    // Dados enviados
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) throw new Exception("Nenhum dado recebido");

    $produto     = trim($input['produto'] ?? '');
    $dose        = trim($input['dose'] ?? '');
    $motivo      = ($input['motivo'] == '2') ? 'Controle' : 'Prevenção';
    $obs         = trim($input['observacoes'] ?? '');
    $estufa_id   = intval($input['estufa_id'] ?? 0);
    $bancada_nome= trim($input['bancada_nome'] ?? '');

    if ($produto == '') throw new Exception("Produto não informado");

    // Cria apontamento principal
    $stmt = $mysqli->prepare("INSERT INTO apontamentos (propriedade_id, tipo, data, observacoes, status) VALUES (?, 'Defensivo', CURDATE(), ?, 'registro')");
    $stmt->bind_param("is", $propriedade_id, $obs);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // Detalhes
    $detalhes = [
        ['campo' => 'Estufa', 'valor' => $estufa_id],
        ['campo' => 'Bancada', 'valor' => $bancada_nome],
        ['campo' => 'Produto', 'valor' => $produto],
        ['campo' => 'Dose', 'valor' => $dose],
        ['campo' => 'Motivo', 'valor' => $motivo]
    ];

    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    foreach ($detalhes as $d) {
        $stmt->bind_param("iss", $apontamento_id, $d['campo'], $d['valor']);
        $stmt->execute();
    }
    $stmt->close();

    echo json_encode(['ok' => true, 'id' => $apontamento_id]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
