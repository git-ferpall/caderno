<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/apontamento_arquivos.php';
require_once __DIR__ . '/apontamento_historico.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('method_not_allowed');
    }

    $user_id = caderno_require_user_id();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('id_invalido');
    }

    $motivo = trim((string)($_POST['motivo'] ?? $_POST['observacao'] ?? ''));
    if ($motivo === '') {
        throw new Exception('Informe o motivo da exclusão.');
    }
    if (mb_strlen($motivo) > 2000) {
        throw new Exception('Motivo da exclusão muito longo.');
    }

    garantirColunaApontamentoExcluido($mysqli);
    garantirTabelaApontamentoHistorico($mysqli);

    $stmt = $mysqli->prepare("
        SELECT a.id, a.excluido_em
        FROM apontamentos a
        JOIN propriedades p ON p.id = a.propriedade_id
        WHERE a.id = ? AND p.user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $atual = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$atual) {
        throw new Exception('apontamento_nao_encontrado');
    }

    if (!empty($atual['excluido_em'])) {
        echo json_encode(['ok' => true, 'msg' => 'Apontamento já estava excluído.']);
        exit;
    }

    $agora = date('Y-m-d H:i:s');
    registrarHistoricoApontamento($mysqli, $id, $user_id, 'excluido', '', $agora);
    registrarHistoricoApontamento($mysqli, $id, $user_id, 'motivo_exclusao', '', $motivo);

    $stmtUp = $mysqli->prepare("
        UPDATE apontamentos
        SET excluido_em = ?, motivo_exclusao = ?
        WHERE id = ? AND excluido_em IS NULL
    ");
    $stmtUp->bind_param('ssi', $agora, $motivo, $id);
    $stmtUp->execute();
    $stmtUp->close();

    echo json_encode([
        'ok' => true,
        'msg' => 'Apontamento excluído. Continua visível na linha do tempo e nos relatórios.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => caderno_erro_msg($e)]);
}
