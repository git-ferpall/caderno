<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'method_not_allowed']);
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

    // LOG DEBUG
    file_put_contents("/tmp/debug_coleta.txt", "=== NOVA COLETA " . date("Y-m-d H:i:s") . " ===\n", FILE_APPEND);
    file_put_contents("/tmp/debug_coleta.txt", print_r($_POST, true), FILE_APPEND);

    // Buscar propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $prop = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$prop) throw new Exception("Nenhuma propriedade ativa encontrada");
    $propriedade_id = $prop['id'];

    // Dados principais
    $data         = $_POST['data'] ?? null;
    $areas        = $_POST['area'] ?? [];
    $tipo         = $_POST['tipo'] ?? null;
    $laboratorio  = $_POST['laboratorio'] ?? null;
    $responsavel  = $_POST['responsavel'] ?? null;
    $resultado    = trim($_POST['resultado'] ?? '');
    $obs          = $_POST['obs'] ?? null;

    if (!is_array($areas)) $areas = [$areas];
    if (!$data || empty($areas) || !$tipo) throw new Exception("Campos obrigatórios ausentes");

    // Status depende do resultado
    $status = ($resultado !== '') ? 'concluido' : 'pendente';

    $mysqli->begin_transaction();

    // Inserção principal
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, observacoes, status)
        VALUES (?, 'coleta_analise', ?, ?, ?)
    ");
    $stmt->bind_param("isss", $propriedade_id, $data, $obs, $status);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    file_put_contents("/tmp/debug_coleta.txt", "✅ Inserido apontamento ID=$apontamento_id\n", FILE_APPEND);

    // Inserir detalhes (áreas + outros campos)
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");

    foreach ($areas as $a) {
        $campo = 'area_id';
        $valor = (string)$a;
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();
    }

    $detalhes = [
        'tipo_analise' => $tipo,
        'laboratorio' => $laboratorio,
        'responsavel' => $responsavel
    ];

    // Só grava o resultado se houver texto
    if ($resultado !== '') {
        $detalhes['resultado'] = $resultado;
    }

    foreach ($detalhes as $campo => $valor) {
        if ($valor !== null && $valor !== '') {
            $stmt->bind_param("iss", $apontamento_id, $campo, (string)$valor);
            $stmt->execute();
        }
    }

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Apontamento de Coleta e Análise salvo com sucesso!']);
} catch (Exception $e) {
    if (isset($mysqli)) $mysqli->rollback();
    file_put_contents("/tmp/debug_coleta.txt", "ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Erro inesperado ao salvar coleta.']);
}
