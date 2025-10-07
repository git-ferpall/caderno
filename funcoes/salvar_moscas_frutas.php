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

    // Log inicial
    file_put_contents("/tmp/debug_moscas.txt", "=== NOVA REQUISIÇÃO " . date("Y-m-d H:i:s") . " ===\n", FILE_APPEND);
    file_put_contents("/tmp/debug_moscas.txt", print_r($_POST, true), FILE_APPEND);

    // Propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) throw new Exception("Nenhuma propriedade ativa encontrada");
    $propriedade_id = $prop['id'];

    // Dados recebidos
    $data           = $_POST['data'] ?? null;
    $areas          = $_POST['area'] ?? [];
    $produtos       = $_POST['produto'] ?? [];
    $armadilha      = $_POST['armadilha'] ?? null;
    $atrativo       = $_POST['atrativo'] ?? null;
    $qtd_armadilhas = $_POST['qtd_armadilhas'] ?? null;
    $qtd_moscas     = $_POST['qtd_moscas'] ?? null;
    $obs            = $_POST['obs'] ?? null;

    if (!is_array($areas)) $areas = [$areas];
    if (!is_array($produtos)) $produtos = [$produtos];

    // Validação básica
    if (!$data || empty($areas) || empty($produtos) || !$armadilha || !$atrativo || !$qtd_armadilhas) {
        throw new Exception("Campos obrigatórios ausentes");
    }

    $mysqli->begin_transaction();

    // Status automático
    $status = (!empty($qtd_moscas) && $qtd_moscas > 0) ? 'concluido' : 'pendente';

    // === Inserção principal ===
    $stmtMain = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'moscas_frutas', ?, ?, ?, ?)
    ");
    $stmtMain->bind_param("issss", $propriedade_id, $data, $qtd_moscas, $obs, $status);
    if (!$stmtMain->execute()) {
        throw new Exception("Falha ao inserir apontamento principal: " . $stmtMain->error);
    }
    $apontamento_id = $stmtMain->insert_id;
    $stmtMain->close();

    file_put_contents("/tmp/debug_moscas.txt", "Inserido apontamento ID=$apontamento_id\n", FILE_APPEND);

    // === Inserção dos detalhes ===
    $stmtDet = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");

    // Áreas
    foreach ($areas as $a) {
        $campo = "area_id";
        $valor = (string)$a;
        $stmtDet->bind_param("iss", $apontamento_id, $campo, $valor);
        if (!$stmtDet->execute()) {
            throw new Exception("Erro ao inserir área: " . $stmtDet->error);
        }
    }

    // Produtos
    foreach ($produtos as $p) {
        $campo = "produto";
        $valor = (string)$p;
        $stmtDet->bind_param("iss", $apontamento_id, $campo, $valor);
        if (!$stmtDet->execute()) {
            throw new Exception("Erro ao inserir produto: " . $stmtDet->error);
        }
    }

    // Outros detalhes
    $detalhes = [
        'armadilha' => $armadilha,
        'atrativo' => $atrativo,
        'qtd_armadilhas' => $qtd_armadilhas
    ];

    foreach ($detalhes as $campo => $valor) {
        $stmtDet->bind_param("iss", $apontamento_id, $campo, (string)$valor);
        if (!$stmtDet->execute()) {
            throw new Exception("Erro ao inserir detalhe $campo: " . $stmtDet->error);
        }
    }

    $stmtDet->close();
    $mysqli->commit();

    file_put_contents("/tmp/debug_moscas.txt", "Concluído com sucesso.\n", FILE_APPEND);

    echo json_encode(['ok' => true, 'msg' => 'Apontamento de Moscas das Frutas salvo com sucesso!']);

} catch (Exception $e) {
    if (isset($mysqli)) $mysqli->rollback();
    file_put_contents("/tmp/debug_moscas.txt", "ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'exception', 'msg' => $e->getMessage()]);
}
