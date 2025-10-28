<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

try {
    // === Identifica usuário ===
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) {
        throw new Exception('Usuário não autenticado');
    }

    // === Debug POST ===
    file_put_contents(__DIR__ . "/debug_fertilizante.txt", print_r($_POST, true) . "\n---\n", FILE_APPEND);

    // === Propriedade ativa ===
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $prop = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$prop) throw new Exception('Nenhuma propriedade ativa encontrada');
    $propriedade_id = $prop['id'];

    // === Dados do formulário ===
    $estufa_id     = $_POST['estufa_id'] ?? null;
    $area_id       = $_POST['area_id'] ?? ($_POST['bancada_area_id'] ?? null);
    $bancada_nome  = $_POST['bancada'] ?? ''; // opcional
    $produto_id    = $_POST['produto_id'] ?? ($_POST['bproduto'] ?? null);
    $produto_nome  = trim($_POST['produto_nome'] ?? '');
    $dose          = trim($_POST['dose'] ?? '');
    $tipo          = trim($_POST['tipo'] ?? '');
    $obs           = trim($_POST['obs'] ?? '');
    $data          = date('Y-m-d');

    if (!$estufa_id || !$area_id || !$produto_id) {
        throw new Exception("Campos obrigatórios não informados (estufa_id, area_id, produto_id)");
    }

    $mysqli->begin_transaction();

    // 🧩 1. Inserir apontamento
    $tipo_apontamento = "fertilizante";
    $status = "pendente";
    $quantidade = ($dose !== '') ? floatval($dose) : 0.0;

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdss", $propriedade_id, $tipo_apontamento, $data, $quantidade, $obs, $status);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    if (!$apontamento_id) throw new Exception("Falha ao criar apontamento principal");

    // 🧩 2. Detalhes complementares
    $detalhes = [
        'estufa_id'      => $estufa_id,
        'area_id'        => $area_id,
        'bancada_nome'   => $bancada_nome,
        'produto_id'     => $produto_id,
        'produto_nome'   => $produto_nome,
        'tipo_aplicacao' => ($tipo == 1) ? "Foliar" : "Solução"
    ];

    foreach ($detalhes as $campo => $valor) {
        if ($valor !== '') {
            $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
            $stmt->execute();
            $stmt->close();
        }
    }

    $mysqli->commit();
    echo json_encode(['ok' => true, 'msg' => '✅ Fertilizante salvo com sucesso']);

} catch (Exception $e) {
    if ($mysqli->in_transaction) $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
