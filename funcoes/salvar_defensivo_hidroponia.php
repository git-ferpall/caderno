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
    if (!$user_id) throw new Exception("Usuário não autenticado");

    // === Propriedade ativa ===
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $prop = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$prop) throw new Exception("Nenhuma propriedade ativa encontrada");
    $propriedade_id = $prop['id'];

    // === Dados do formulário ===
    $estufa_id = $_POST['estufa_id'] ?? null;
    $bancada_nome = $_POST['area_id'] ?? null;
    $inseticida = $_POST['inseticida'] ?? '';
    $inseticida_outro = trim($_POST['inseticida_outro'] ?? '');
    $dose = trim($_POST['dose'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');
    $obs = trim($_POST['obs'] ?? '');
    $data = date('Y-m-d');

    if (!$estufa_id || !$bancada_nome || !$inseticida) {
        throw new Exception("Campos obrigatórios não informados");
    }

    // === Busca vínculos da bancada ===
    $stmt = $mysqli->prepare("SELECT area_id, produto_id FROM bancadas WHERE estufa_id = ? AND nome LIKE CONCAT('%', ?, '%') LIMIT 1");
    $stmt->bind_param("is", $estufa_id, $bancada_nome);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) throw new Exception("Bancada não encontrada ou sem vínculos");

    $area_id_real = $res['area_id'];
    $produto_id_real = $res['produto_id'];

    // === Resolve nome do inseticida ===
    if ($inseticida === "outro" && $inseticida_outro !== "") {
        $nome_inseticida = $inseticida_outro;
    } else {
        $stmt = $mysqli->prepare("SELECT nome FROM inseticidas WHERE id = ?");
        $stmt->bind_param("i", $inseticida);
        $stmt->execute();
        $nome_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $nome_inseticida = $nome_row['nome'] ?? 'Não especificado';
    }

    // === Cria apontamento ===
    $mysqli->begin_transaction();
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'defensivo', ?, ?, ?, 'pendente')
    ");
    $stmt->bind_param("isds", $propriedade_id, $data, $dose, $obs);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    if (!$apontamento_id) throw new Exception("Falha ao criar apontamento principal");

    // === Detalhes ===
    $detalhes = [
        ['area_id', $area_id_real],
        ['produto_id', $produto_id_real],
        ['inseticida', $nome_inseticida],
        ['motivo', $motivo]
    ];

    foreach ($detalhes as [$campo, $valor]) {
        $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();
        $stmt->close();
    }

    $mysqli->commit();
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    if ($mysqli->errno) $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
