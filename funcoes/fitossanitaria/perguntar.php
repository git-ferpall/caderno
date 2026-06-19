<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../apontamento_arquivos.php';
require_once __DIR__ . '/score.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

session_start();
$user_id = (int) ($_SESSION['user_id'] ?? 0);
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = (int) ($payload['sub'] ?? 0);
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$pergunta = trim((string) ($input['pergunta'] ?? ''));
$area_id = (int) ($input['area_id'] ?? 0);

if ($area_id <= 0 || $pergunta === '') {
    echo json_encode(['ok' => false, 'msg' => 'Informe área e pergunta.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$prop = obterPropriedadeAtiva($mysqli, $user_id);
if (!$prop) {
    echo json_encode(['ok' => false, 'msg' => 'Nenhuma propriedade ativa'], JSON_UNESCAPED_UNICODE);
    exit;
}

$painel = fsMontarPainelArea($mysqli, $user_id, (int) $prop['id'], $area_id);
if (empty($painel['ok'])) {
    echo json_encode($painel, JSON_UNESCAPED_UNICODE);
    exit;
}

$resultado = fsResponderPerguntaFitossanitaria($mysqli, $painel, $pergunta);

if (!empty($resultado['ok'])) {
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($resultado['msg'] ?? '') === 'perguntar_ia') {
    try {
        $gpt = fsPerguntarComGpt($painel, $pergunta);
        echo json_encode($gpt, JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        echo json_encode([
            'ok' => true,
            'resposta' => 'Não encontrei uma regra específica. '
                . ($painel['recomendacao'] ?? 'Consulte o agrônomo responsável.'),
            'fonte' => 'fallback',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
