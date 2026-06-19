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

$area_id = (int) ($input['area_id'] ?? 0);
$texto = trim((string) ($input['texto'] ?? ''));

if ($area_id <= 0 || $texto === '') {
    echo json_encode(['ok' => false, 'msg' => 'Informe área e texto da validação.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!fsTabelaValidacaoExiste($mysqli)) {
    echo json_encode([
        'ok' => false,
        'msg' => 'Tabela de validação não encontrada. Execute scripts/migrations/fitossanitaria_fase2.sql',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$prop = obterPropriedadeAtiva($mysqli, $user_id);
if (!$prop) {
    echo json_encode(['ok' => false, 'msg' => 'Nenhuma propriedade ativa'], JSON_UNESCAPED_UNICODE);
    exit;
}

$propriedade_id = (int) $prop['id'];
$area = fsBuscarArea($mysqli, $user_id, $propriedade_id, $area_id);
if (!$area) {
    echo json_encode(['ok' => false, 'msg' => 'Área não encontrada'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $mysqli->prepare('
    INSERT INTO fitossanitaria_validacao (propriedade_id, area_id, user_id, texto)
    VALUES (?, ?, ?, ?)
');
$stmt->bind_param('iiis', $propriedade_id, $area_id, $user_id, $texto);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['ok' => false, 'msg' => 'Erro ao salvar validação'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'msg' => 'Validação registrada.',
    'validacao' => [
        'texto' => $texto,
        'criado_em' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
    ],
], JSON_UNESCAPED_UNICODE);
