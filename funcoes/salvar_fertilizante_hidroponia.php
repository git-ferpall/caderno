<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

// === LOG DE DEPURAÇÃO ===
function log_debug($txt) {
    file_put_contents("/tmp/debug_fertilizante.txt", date("Y-m-d H:i:s") . " | " . $txt . "\n", FILE_APPEND);
}

try {
    log_debug("=== NOVA REQUISIÇÃO ===");
    log_debug("POST: " . print_r($_POST, true));
    log_debug("SESSION: " . print_r($_SESSION, true));

    // === Identifica o usuário logado ===
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) throw new Exception('Usuário não autenticado');
    log_debug("UserID: $user_id");

    // === Propriedade ativa ===
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) throw new Exception('Nenhuma propriedade ativa');
    $propriedade_id = (int)$prop['id'];
    log_debug("Propriedade ativa: $propriedade_id");

    // === Dados do formulário ===
    $estufa_id   = (int)($_POST['estufa_id'] ?? 0);
    $area_id     = (int)($_POST['area_id'] ?? 0);
    $produto_id  = (int)($_POST['produto_id'] ?? 0);
    $dose        = trim($_POST['dose'] ?? '');
    $tipo        = trim($_POST['tipo'] ?? '');
    $obs         = trim($_POST['obs'] ?? '');
    $data        = date('Y-m-d');

    log_debug("Dados recebidos: estufa_id=$estufa_id | area_id=$area_id | produto_id=$produto_id | dose=$dose | tipo=$tipo | obs=$obs");

    if ($estufa_id <= 0 || $area_id <= 0) throw new Exception('Estufa ou área não identificada.');
    if ($produto_id <= 0) throw new Exception('Fertilizante não selecionado.');

    $mysqli->begin_transaction();

    // 1️⃣ Apontamento principal
    $tipo_apontamento = "fertilizante";
    $status = "pendente";
    $quantidade = ($dose !== '') ? floatval($dose) : 0;

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdss", $propriedade_id, $tipo_apontamento, $data, $quantidade, $obs, $status);
    $ok = $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    log_debug("Insert apontamentos OK? " . ($ok ? 'sim' : 'nao') . " | ID=$apontamento_id | Erro={$stmt->error}");
    $stmt->close();

    if (!$apontamento_id) throw new Exception('Falha ao criar apontamento.');

    // 2️⃣ Detalhes do apontamento
    $detalhes = [
        ['campo' => 'estufa_id',      'valor' => $estufa_id],
        ['campo' => 'area_id',        'valor' => $area_id],
        ['campo' => 'produto_id',     'valor' => $produto_id],
        ['campo' => 'tipo_aplicacao', 'valor' => ($tipo == 1 ? "Foliar" : "Solução")]
    ];

    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    foreach ($detalhes as $d) {
        $stmt->bind_param("iss", $apontamento_id, $d['campo'], $d['valor']);
        $ok = $stmt->execute();
        log_debug("Insert detalhe: campo={$d['campo']} | valor={$d['valor']} | OK=" . ($ok ? 'sim' : 'nao') . " | Erro={$stmt->error}");
    }
    $stmt->close();

    $mysqli->commit();
    log_debug("✅ COMMIT efetuado com sucesso.");

    echo json_encode(['ok' => true, 'msg' => 'Fertilizante salvo com sucesso!']);

} catch (Exception $e) {
    $mysqli->rollback();
    log_debug("❌ ERRO: " . $e->getMessage());
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
