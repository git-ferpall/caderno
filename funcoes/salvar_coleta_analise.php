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
    $user_id = caderno_require_user_id();
    if (!$user_id) {
        throw new Exception("Usuário não autenticado.");
    }

    // Buscar propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) throw new Exception("Nenhuma propriedade ativa encontrada.");
    $propriedade_id = $prop['id'];

    // Capturar dados
    $data         = $_POST['data'] ?? null;
    $areas        = $_POST['area'] ?? [];
    $tipo         = $_POST['tipo'] ?? null;
    $laboratorio  = $_POST['laboratorio'] ?? null;
    $responsavel  = $_POST['responsavel'] ?? null;
    $resultado    = $_POST['resultado'] ?? null;
    $obs          = $_POST['obs'] ?? null;

    if (!is_array($areas)) $areas = [$areas];
    if (!$data || empty($areas) || !$tipo) {
        throw new Exception("Campos obrigatórios ausentes.");
    }

    require_once __DIR__ . '/../configuracao/ownership.php';
    caderno_validar_areas_usuario($mysqli, $user_id, $areas, $propriedade_id);

    $mysqli->begin_transaction();

    // === Status automático ===
    $status = (!empty($resultado)) ? 'concluido' : 'pendente';

    // === Inserção principal ===
    $stmtApont = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'coleta_analise', ?, NULL, ?, ?)
    ");
    $stmtApont->bind_param("isss", $propriedade_id, $data, $obs, $status);
    $stmtApont->execute();
    $apontamento_id = $stmtApont->insert_id;
    $stmtApont->close();

    // === Inserir detalhes ===
    $stmtDetalhe = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");

    // Áreas
    foreach ($areas as $a) {
        $campo = "area_id";
        $valor = (string)$a;
        $stmtDetalhe->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmtDetalhe->execute();
    }

    // Tipo de análise
    $campo = "tipo_analise";
    $valor = (string)$tipo;
    $stmtDetalhe->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmtDetalhe->execute();

    // Laboratório
    if (!empty($laboratorio)) {
        $campo = "laboratorio";
        $valor = (string)$laboratorio;
        $stmtDetalhe->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmtDetalhe->execute();
    }

    // Responsável
    if (!empty($responsavel)) {
        $campo = "responsavel";
        $valor = (string)$responsavel;
        $stmtDetalhe->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmtDetalhe->execute();
    }

    // Resultado (só se informado)
    if (!empty($resultado)) {
        $campo = "resultado";
        $valor = (string)$resultado;
        $stmtDetalhe->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmtDetalhe->execute();
    }

    $stmtDetalhe->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Apontamento de Coleta e Análise salvo com sucesso!']);
}
catch (Exception $e) {
    if (isset($mysqli)) $mysqli->rollback();
    error_log('[salvar_coleta_analise] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Erro inesperado ao salvar coleta.']);
}
