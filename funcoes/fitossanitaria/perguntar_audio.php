<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../apontamento_arquivos.php';
require_once __DIR__ . '/../ia/ia_helpers.php';
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

$area_id = (int) ($_POST['area_id'] ?? 0);
if ($area_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'Selecione uma área.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_FILES['audio']['tmp_name'])) {
    echo json_encode(['ok' => false, 'msg' => 'Envie um áudio.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tmp = (string) $_FILES['audio']['tmp_name'];
$mime = $_FILES['audio']['type'] ?: 'audio/webm';
$size = (int) ($_FILES['audio']['size'] ?? 0);

if ($size <= 0 || $size > 25 * 1024 * 1024) {
    echo json_encode(['ok' => false, 'msg' => 'Áudio inválido ou muito grande (máx. 25 MB).'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $transcricao = iaTranscreverAudio($tmp, $mime, 'fitossanitaria');
    $resultado = fsProcessarPerguntaArea($mysqli, $user_id, $area_id, $transcricao);
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => caderno_erro_msg($e)], JSON_UNESCAPED_UNICODE);
}
