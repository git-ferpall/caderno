<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'err' => 'method_not_allowed']);
    exit;
}

try {
    session_start();

    // Identifica usuário logado
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

    // Normaliza quantidade de moscas
    if ($qtd_moscas === '' || $qtd_moscas === null) {
        $qtd_moscas = 0;
    }

    // Validação básica
    if (!$data || empty($areas) || empty($produtos) || !$armadilha || !$atrativo || !$qtd_armadilhas) {
        throw new Exception("Campos obrigatórios ausentes");
    }

    // Define status automaticamente
    $status = ((float)$qtd_moscas > 0) ? 'concluido' : 'pendente';

    $mysqli->begin_transaction();

    // === INSERÇÃO PRINCIPAL ===
    $stmtMain = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'moscas_frutas', ?, ?, ?, ?)
    ");

    if (!$stmtMain) {
        throw new Exception("Erro ao preparar INSERT principal: " . $mysqli->error);
    }

    $stmtMain->bind_param("isdss", $propriedade_id, $data, $qtd_moscas, $obs, $status);

    if (!$stmtMain->execute()) {
        throw new Exception("Erro ao executar INSERT principal: " . $stmtMain->error);
    }

    $apontamento_id = $stmtMain->insert_id;
    file_put_contents("/tmp/debug_moscas.txt", "✅ Inserido apontamento ID=$apontamento_id\n", FILE_APPEND);
    $stmtMain->close();

    // === INSERÇÃO DE DETALHES ===
    $stmtDet = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    if (!$stmtDet) throw new Exception("Erro ao preparar INSERT de detalhes: " . $mysqli->error);

    foreach ($areas as $area_id) {
        file_put_contents("/tmp/debug_moscas.txt", "Inserindo área $area_id...\n", FILE_APPEND);
        $campo = "area_id";
        $valor = (string)$area_id;
        $stmtDet->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmtDet->execute();
    }

    foreach ($produtos as $produto_id) {
        file_put_contents("/tmp/debug_moscas.txt", "Inserindo produto $produto_id...\n", FILE_APPEND);
        $campo = "produto";
        $valor = (string)$produto_id;
        $stmtDet->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmtDet->execute();
    }

    $detalhes = [
        'armadilha' => $armadilha,
        'atrativo' => $atrativo,
        'qtd_armadilhas' => $qtd_armadilhas
    ];

    foreach ($detalhes as $campo => $valor) {
    $valorStr = (string)$valor;
    file_put_contents("/tmp/debug_moscas.txt", "Inserindo detalhe $campo=$valorStr...\n", FILE_APPEND);

        $stmtDet->bind_param("iss", $apontamento_id, $campo, $valorStr);
        if (!$stmtDet->execute()) {
            throw new Exception("Erro ao inserir detalhe $campo: " . $stmtDet->error);
        }
    }    



    $stmtDet->close();
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
