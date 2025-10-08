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

    if (!$user_id) throw new Exception("Usuário não autenticado.");

    file_put_contents("/tmp/debug_visita.txt", "=== NOVA VISITA " . date("Y-m-d H:i:s") . " ===\n", FILE_APPEND);
    file_put_contents("/tmp/debug_visita.txt", print_r($_POST, true), FILE_APPEND);

    // Propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) throw new Exception("Nenhuma propriedade ativa encontrada.");
    $propriedade_id = $prop['id'];

    // Dados
    $data        = $_POST['data'] ?? null;
    $areas       = $_POST['area'] ?? [];
    $responsavel = $_POST['responsavel'] ?? null;
    $empresa     = $_POST['empresa'] ?? null;
    $objetivo    = $_POST['objetivo'] ?? null;
    $conclusao   = $_POST['conclusao'] ?? null;
    $obs         = $_POST['obs'] ?? null;

    if (!is_array($areas)) $areas = [$areas];
    if (!$data || empty($areas) || !$responsavel) throw new Exception("Campos obrigatórios ausentes.");

    $mysqli->begin_transaction();

    // Define status
    $status = (!empty($conclusao)) ? 'concluido' : 'pendente';

    // Inserção principal
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'visita_tecnica', ?, NULL, ?, ?)
    ");
    $stmt->bind_param("isss", $propriedade_id, $data, $obs, $status);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    file_put_contents("/tmp/debug_visita.txt", "✅ Inserido apontamento ID=$apontamento_id\n", FILE_APPEND);

    // Detalhes
    $stmtDet = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");

    foreach ($areas as $a) {
        $campo = "area_id";
        $valor = (string)$a;
        $stmtDet->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmtDet->execute();
    }

    $detalhes = [
        'responsavel' => $responsavel,
        'empresa'     => $empresa ?? '',
        'objetivo'    => $objetivo ?? '',
        'conclusao'   => $conclusao ?? ''
    ];

    foreach ($detalhes as $campo => $valor) {
        if (trim($valor) !== '') {
            $stmtDet->bind_param("iss", $apontamento_id, $campo, $valor);
            $stmtDet->execute();
        }
    }

    $stmtDet->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Apontamento de Visita Técnica salvo com sucesso!']);

} catch (Exception $e) {
    if (isset($mysqli)) $mysqli->rollback();
    file_put_contents("/tmp/debug_visita.txt", "ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Erro inesperado ao salvar visita.']);
}
