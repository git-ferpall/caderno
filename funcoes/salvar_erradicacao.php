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
    $user_id = caderno_require_user_id();
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

    require_once __DIR__ . '/../configuracao/ownership.php';
    caderno_validar_areas_usuario($mysqli, $user_id, $areas, $propriedade_id);
    caderno_validar_produtos_usuario($mysqli, $user_id, $produtos);

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

    // Inserir Áreas
    $stmtArea = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, 'area_id', ?)");
    foreach ($areas as $a) {
        $valor = (string)$a;
        $stmtArea->bind_param("is", $apontamento_id, $valor);
        if (!$stmtArea->execute()) throw new Exception("Erro ao inserir área: " . $stmtArea->error);
    }
    $stmtArea->close();

    // Inserir Produtos
    $stmtProd = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, 'produto', ?)");
    foreach ($produtos as $p) {
        $valor = (string)$p;
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
        $stmtDet->bind_param("iss", $apontamento_id, $campo, $valor);
        if (!$stmtDet->execute()) throw new Exception("Erro ao inserir detalhe {$campo}: " . $stmtDet->error);
    }
    $stmtDet->close();

    // Commit
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Apontamento de Erradicação salvo com sucesso!']);

} catch (Exception $e) {
    if (isset($mysqli)) $mysqli->rollback();
    error_log('[salvar_erradicacao] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok'  => false,
        'err' => 'exception',
        'msg' => caderno_erro_msg($e)
    ]);
}
