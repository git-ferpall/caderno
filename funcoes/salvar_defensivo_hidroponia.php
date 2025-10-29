<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

try {
    // === Autenticação ===
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) throw new Exception('Usuário não autenticado');

    // === Propriedade ativa ===
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $prop = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$prop) throw new Exception('Nenhuma propriedade ativa encontrada');
    $propriedade_id = $prop['id'];

    // === Dados do formulário ===
    $estufa_id      = $_POST['estufa_id'] ?? null;
    $bancada_nome   = $_POST['area_id'] ?? null; // vem do nome da bancada (ex: Bancada 01)
    $defensivo_id   = $_POST['produto_id'] ?? null;
    $produto_outro  = trim($_POST['produto_outro'] ?? '');
    $dose           = trim($_POST['dose'] ?? '');
    $motivo         = trim($_POST['motivo'] ?? '');
    $obs            = trim($_POST['obs'] ?? '');
    $data           = date('Y-m-d');
    $data_conclusao = date('Y-m-d H:i:s');

    if (!$bancada_nome || !$defensivo_id || !$estufa_id) {
        throw new Exception("Campos obrigatórios não informados (bancada, estufa ou defensivo)");
    }

    // === Busca área e produto da bancada ===
    $stmt = $mysqli->prepare("
        SELECT area_id, produto_id 
        FROM bancadas 
        WHERE estufa_id = ? AND nome LIKE CONCAT('%', ?, '%') 
        LIMIT 1
    ");
    $stmt->bind_param("is", $estufa_id, $bancada_nome);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) throw new Exception("Bancada não encontrada ou sem vínculos com área/produto.");

    $area_id_real = $res['area_id'];
    $produto_id_real = $res['produto_id'];

    // === Busca nome do defensivo ===
    if ($defensivo_id === 'outro' && $produto_outro !== '') {
        $defensivo_nome = $produto_outro;
    } else {
        $stmt = $mysqli->prepare("SELECT nome FROM inseticidas WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $defensivo_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $defensivo_nome = $res['nome'] ?? "Defensivo não identificado";
    }

    // === Traduz motivo numérico ===
    $motivo_txt = ($motivo == 1) ? "Prevenção" : "Controle";

    // === Inicia transação ===
    $mysqli->begin_transaction();

    // === Cria apontamento principal ===
    $tipo_apontamento = "defensivo";
    $status = "concluido";
    $quantidade = ($dose !== '') ? floatval($dose) : 0.0;

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, data_conclusao, quantidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdsss",
        $propriedade_id,
        $tipo_apontamento,
        $data,
        $data_conclusao,
        $quantidade,
        $obs,
        $status
    );
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    if (!$apontamento_id) throw new Exception("Falha ao criar apontamento principal.");

    // === Salva detalhes ===
    $detalhes = [
        ['area_id', $area_id_real],
        ['produto_id', $produto_id_real],
        ['defensivo', $defensivo_nome],
        ['motivo', $motivo_txt],
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
