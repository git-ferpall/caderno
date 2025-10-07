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

    // === Identificação do usuário ===
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) throw new Exception("Usuário não autenticado");

    // === Log inicial ===
    file_put_contents("/tmp/debug_moscas.txt", "=== NOVA REQUISIÇÃO " . date("Y-m-d H:i:s") . " ===\n", FILE_APPEND);
    file_put_contents("/tmp/debug_moscas.txt", print_r($_POST, true), FILE_APPEND);

    // === Buscar propriedade ativa ===
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) throw new Exception("Nenhuma propriedade ativa encontrada");
    $propriedade_id = $prop['id'];

    // === Dados ===
    $data           = $_POST['data'] ?? null;
    $areas          = $_POST['area'] ?? [];
    $produtos       = $_POST['produto'] ?? [];
    $armadilha      = $_POST['armadilha'] ?? null;
    $atrativo       = $_POST['atrativo'] ?? null;
    $qtd_armadilhas = $_POST['qtd_armadilhas'] ?? 0;
    $qtd_moscas     = $_POST['qtd_moscas'] ?? null;
    $obs            = $_POST['obs'] ?? null;

    if (!is_array($areas)) $areas = [$areas];
    if (!is_array($produtos)) $produtos = [$produtos];

    if (!$data || empty($areas) || empty($produtos) || !$armadilha || !$atrativo) {
        throw new Exception("Campos obrigatórios ausentes");
    }

    // === Início da transação ===
    $mysqli->begin_transaction();

    // Define status automaticamente
    $status = (!empty($qtd_moscas) && floatval($qtd_moscas) > 0) ? 'concluido' : 'pendente';

    // === Inserção principal ===
    $stmtMain = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'moscas_frutas', ?, ?, ?, ?)
    ");
    $stmtMain->bind_param("isdss", $propriedade_id, $data, $qtd_armadilhas, $obs, $status);
    $stmtMain->execute();
    $apontamento_id = $stmtMain->insert_id;
    $stmtMain->close();

    file_put_contents("/tmp/debug_moscas.txt", "✅ Inserido apontamento ID={$apontamento_id}\n", FILE_APPEND);

    // === Inserir Áreas ===
    $stmtArea = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, 'area_id', ?)
    ");
    foreach ($areas as $a) {
        $valor = (string)$a;
        file_put_contents("/tmp/debug_moscas.txt", "Inserindo área {$valor}...\n", FILE_APPEND);
        $stmtArea->bind_param("is", $apontamento_id, $valor);
        if (!$stmtArea->execute()) throw new Exception("Erro ao inserir área: " . $stmtArea->error);
    }
    $stmtArea->close();

    // === Inserir Produtos ===
    $stmtProd = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, 'produto', ?)
    ");
    foreach ($produtos as $p) {
        $valor = (string)$p;
        file_put_contents("/tmp/debug_moscas.txt", "Inserindo produto {$valor}...\n", FILE_APPEND);
        $stmtProd->bind_param("is", $apontamento_id, $valor);
        if (!$stmtProd->execute()) throw new Exception("Erro ao inserir produto: " . $stmtProd->error);
    }
    $stmtProd->close();

    // === Inserir Detalhes ===
    $stmtDet = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, ?, ?)
    ");

    // Campos fixos
    $detalhes = [
        'armadilha' => $armadilha,
        'atrativo'  => $atrativo,
    ];

    // Só adiciona qtd_moscas se valor > 0
    if (!empty($qtd_moscas) && floatval($qtd_moscas) > 0) {
        $detalhes['qtd_moscas'] = (string)$qtd_moscas;
    }

    foreach ($detalhes as $campo => $valor) {
        $valorStr = (string)$valor;
        file_put_contents("/tmp/debug_moscas.txt", "Inserindo detalhe {$campo}={$valorStr}...\n", FILE_APPEND);
        $stmtDet->bind_param("iss", $apontamento_id, $campo, $valorStr);
        if (!$stmtDet->execute()) {
            throw new Exception("Erro ao inserir detalhe {$campo}: " . $stmtDet->error);
        }
    }
    $stmtDet->close();

    // === Finaliza transação ===
    $mysqli->commit();
    file_put_contents("/tmp/debug_moscas.txt", "✅ Finalizado com sucesso!\n", FILE_APPEND);

    echo json_encode(['ok' => true, 'msg' => 'Apontamento de Moscas das Frutas salvo com sucesso!']);

} catch (Exception $e) {
    if (isset($mysqli)) $mysqli->rollback();
    file_put_contents("/tmp/debug_moscas.txt", "❌ ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'err' => 'exception',
        'msg' => $e->getMessage()
    ]);
}
