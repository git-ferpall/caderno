<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../apontamento_arquivos.php';
require_once __DIR__ . '/score.php';

header('Content-Type: application/json; charset=utf-8');

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

$prop = obterPropriedadeAtiva($mysqli, $user_id);
if (!$prop) {
    echo json_encode(['ok' => false, 'msg' => 'Nenhuma propriedade ativa'], JSON_UNESCAPED_UNICODE);
    exit;
}

$propriedade_id = (int) $prop['id'];
$area_id = isset($_GET['area_id']) ? (int) $_GET['area_id'] : 0;

if ($area_id > 0) {
    $painel = fsMontarPainelArea($mysqli, $user_id, $propriedade_id, $area_id);
    echo json_encode($painel, JSON_UNESCAPED_UNICODE);
    exit;
}

$areas = fsListarScoresAreas($mysqli, $user_id, $propriedade_id);
echo json_encode([
    'ok' => true,
    'propriedade' => [
        'id' => $propriedade_id,
        'nome' => (string) ($prop['nome_razao'] ?? ''),
    ],
    'areas' => $areas,
    'data_referencia' => date('Y-m-d'),
], JSON_UNESCAPED_UNICODE);
