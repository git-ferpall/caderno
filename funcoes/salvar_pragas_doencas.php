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

    // === Usuário autenticado ===
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) throw new Exception("Usuário não autenticado");

    // === Log inicial ===
    $logFile = "/tmp/debug_pragas.txt";
    file_put_contents($logFile, "=== NOVA REQUISIÇÃO " . date("Y-m-d H:i:s") . " ===\n", FILE_APPEND);
    file_put_contents($logFile, print_r($_POST, true), FILE_APPEND);

    // === Propriedade ativa ===
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) throw new Exception("Nenhuma propriedade ativa encontrada");
    $propriedade_id = $prop['id'];

    // === Dados do formulário ===
    $data            = $_POST['data'] ?? null;
    $areas           = $_POST['area'] ?? [];
    $produtos        = $_POST['produto'] ?? [];
    $praga           = trim($_POST['praga'] ?? '');
    $doenca          = trim($_POST['doenca'] ?? '');
    $intensidade     = $_POST['intensidade'] ?? '';
    $acao_corretiva  = trim($_POST['acao_corretiva'] ?? '');
    $responsavel     = trim($_POST['responsavel'] ?? '');
    $obs             = trim($_POST['obs'] ?? '');

    if (!is_array($areas)) $areas = [$areas];
    if (!is_array($produtos)) $produtos = [$produtos];

    // === Validação mínima ===
    if (!$data || empty($areas) || empty($produtos)) {
        throw new Exception("Campos obrigatórios ausentes");
    }

    // === Início da transação ===
    $mysqli->begin_transaction();

    // === Define status automático ===
    $status = ($acao_corretiva !== '') ? 'concluido' : 'pendente';

    // === Inserção principal ===
    $stmtMain = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, observacoes, status)
        VALUES (?, 'pragas_doencas', ?, ?, ?)
    ");
    $stmtMain->bind_param("isss", $propriedade_id, $data, $obs, $status);
    $stmtMain->execute();
    $apontamento_id = $stmtMain->insert_id;
    $stmtMain->close();

    file_put_contents($logFile, "✅ Inserido apontamento ID={$apontamento_id}\n", FILE_APPEND);

    // === Inserir áreas ===
    $stmtArea = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, 'area_id', ?)
    ");
    foreach ($areas as $a) {
        $valor = (string)$a;
        file_put_contents($logFile, "Inserindo área {$valor}...\n", FILE_APPEND);
        $stmtArea->bind_param("is", $apontamento_id, $valor);
        if (!$stmtArea->execute()) throw new Exception("Erro ao inserir área: " . $stmtArea->error);
    }
    $stmtArea->close();

    // === Inserir produtos ===
    $stmtProd = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, 'produto_id', ?)
    ");
    foreach ($produtos as $p) {
        $valor = (string)$p;
        file_put_contents($logFile, "Inserindo produto {$valor}...\n", FILE_APPEND);
        $stmtProd->bind_param("is", $apontamento_id, $valor);
        if (!$stmtProd->execute()) throw new Exception("Erro ao inserir produto: " . $stmtProd->error);
    }
    $stmtProd->close();

    // === Inserir demais detalhes ===
    $stmtDet = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, ?, ?)
    ");

    $detalhes = [
        'praga'          => $praga,
        'doenca'         => $doenca,
        'intensidade'    => $intensidade,
        'acao_corretiva' => $acao_corretiva,
        'responsavel'    => $responsavel,
    ];

    foreach ($detalhes as $campo => $valor) {
        if (trim($valor) === '') continue; // pula campos vazios
        $valorStr = (string)$valor;
        file_put_contents($logFile, "Inserindo detalhe {$campo}={$valorStr}...\n", FILE_APPEND);
        $stmtDet->bind_param("iss", $apontamento_id, $campo, $valorStr);
        if (!$stmtDet->execute()) throw new Exception("Erro ao inserir detalhe {$campo}: " . $stmtDet->error);
    }
    $stmtDet->close();

    // === Commit ===
    $mysqli->commit();
    file_put_contents($logFile, "✅ Finalizado com sucesso!\n", FILE_APPEND);

    echo json_encode(['ok' => true, 'msg' => 'Apontamento de Pragas e Doenças salvo com sucesso!']);

} catch (Exception $e) {
    if (isset($mysqli)) $mysqli->rollback();
    file_put_contents("/tmp/debug_pragas.txt", "❌ ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'ok'  => false,
        'err' => 'exception',
        'msg' => $e->getMessage()
    ]);
}
