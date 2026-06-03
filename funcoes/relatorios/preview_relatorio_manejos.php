<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/relatorio_manejos_helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user_id = relatorioManejosUserId();
    $dados = relatorioManejosCarregar($mysqli, $user_id, $_POST);

    echo json_encode([
        'ok' => true,
        'total_registros' => $dados['total_geral'],
        'total_concluidos' => $dados['total_concluidos'],
        'total_pendentes' => $dados['total_pendentes'],
        'total_atrasados' => $dados['total_atrasados'],
        'paginas_estimadas' => $dados['paginas_estimadas'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
