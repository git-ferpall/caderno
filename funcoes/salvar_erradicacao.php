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

    // Log
    $logFile = "/tmp/debug_erradicacao.txt";
    file_put_contents($logFile, "=== NOVA REQUISIÇÃO " . date("Y-m-d H:i:s") . " ===\n", FILE_APPEND);
    file_put_contents($logFile, print_r($_POST, true), FILE_APPEND);

    // Propriedade ativa
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
    $areas       = $_POST['area'] ?? [];
    $produtos    = $_POST['produto'] ?? [];
    $motivo      = trim($_POST['motivo'] ?? '');
    $metodo      = trim($_POST['metodo'] ?? '');
    $quantidade  = trim($_POST['quantidade'] ?? '');
    $obs         = trim($_POST['obs'] ?? '');

    if (!is_array($areas)) $areas = [$areas];
    if (!is_array($produtos)) $produtos = [$produtos];

    if (!$data || empty($areas) || empty($produtos) || !$motivo) {
        throw new Exception("Campos obrigatórios ausentes");
    }

    // Define status automático
    $status = (!empty($quantidade) && $quantidade > 0) ? 'concluido' : 'pendente';

    // Início da transação
    $mysqli->begin_transaction();

    // Inserção principal
    $stmtMain = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'erradicacao', ?, ?, ?, ?)
    ");
    $qtdFinal = ($quantidade === '') ? null : $quantidade;
    $stmtMain->bind_param("isdss", $propriedade_id, $data, $qtdFinal, $obs, $status);
    $stmtMain->execute();
    $apontamento_id = $stmtMain->insert_id;
    $stmtMain->close();

    file_put_contents($logFile, "✅ Inserido apontamento ID={$apontamento_id}\n", FILE_APPEND);

    // Inserir Áreas
    $stmtArea = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, 'area_id', ?)");
    foreach ($areas as $a) {
        $valor = (string)$a;
        file_put_contents($logFile, "Inserindo área {$valor}\n", FILE_APPEND);
        $stmtArea->bind_param("is", $apontamento_id, $valor);
        if (!$stmtArea->execute()) throw new Exception("Erro ao inserir área: " . $stmtArea->error);
    }
    $stmtArea->close();

    // Inserir Produtos
    $stmtProd = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, 'produto', ?)");
    foreach ($produtos as $p) {
        $valor = (string)$p;
        file_put_contents($logFile, "Inserindo produto {$valor}\n", FILE_APPEND);
        $stmtProd->bind_param("is", $apontamento_id, $valor);
        if (!$stmtProd->execute()) throw new Exception("Erro ao inserir produto: " . $stmtProd->error);
    }
    $stmtProd->close();

    // Inserir demais detalhes
    $detalhes = [
        'motivo' => $motivo,
        'metodo' => $metodo
    ];

    $stmtDet = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    foreach ($detalhes as $campo => $valor) {
        if (trim($valor) === '') continue;
        file_put_contents($logFile, "Inserindo detalhe {$campo}={$valor}\n", FILE_APPEND);
        $stmtDet->bind_param("iss", $apontamento_id, $campo, $valor);
        if (!$stmtDet->execute()) throw new Exception("Erro ao inserir detalhe {$campo}: " . $stmtDet->error);
    }
    $stmtDet->close();

    // Commit
    $mysqli->commit();
    file_put_contents($logFile, "✅ Transação finalizada com sucesso\n", FILE_APPEND);

    echo json_encode(['ok' => true, 'msg' => 'Apontamento de Erradicação salvo com sucesso!']);

} catch (Exception $e) {
    if (isset($mysqli)) $mysqli->rollback();
    file_put_contents("/tmp/debug_erradicacao.txt", "❌ ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'ok'  => false,
        'err' => 'exception',
        'msg' => $e->getMessage()
    ]);
}
