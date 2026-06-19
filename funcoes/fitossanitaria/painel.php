<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../apontamento_arquivos.php';
require_once __DIR__ . '/score.php';

header('Content-Type: application/json; charset=utf-8');

function fsPainelJsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

try {
    session_start();
    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = (int) ($payload['sub'] ?? 0);
    }

    if (!$user_id) {
        fsPainelJsonResponse(['ok' => false, 'msg' => 'Usuário não autenticado'], 401);
    }

    $prop = obterPropriedadeAtiva($mysqli, $user_id);
    if (!$prop) {
        fsPainelJsonResponse(['ok' => false, 'msg' => 'Nenhuma propriedade ativa']);
    }

    $propriedade_id = (int) $prop['id'];
    $area_id = isset($_GET['area_id']) ? (int) $_GET['area_id'] : 0;

    if ($area_id > 0) {
        $painel = fsMontarPainelArea($mysqli, $user_id, $propriedade_id, $area_id);
        fsPainelJsonResponse($painel);
    }

    $areas = fsListarScoresAreas($mysqli, $user_id, $propriedade_id);
    fsPainelJsonResponse([
        'ok' => true,
        'propriedade' => [
            'id' => $propriedade_id,
            'nome' => (string) ($prop['nome_razao'] ?? ''),
        ],
        'areas' => $areas,
        'data_referencia' => date('Y-m-d'),
    ]);
} catch (Throwable $e) {
    error_log('fitossanitaria/painel.php: ' . $e->getMessage());
    fsPainelJsonResponse([
        'ok' => false,
        'msg' => 'Erro ao carregar painel fitossanitário. Verifique se as migrations das fases 1–3 foram aplicadas.',
    ], 500);
}
