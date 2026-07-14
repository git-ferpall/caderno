<?php
declare(strict_types=1);

require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/hidroponia_helpers.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    try {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'err' => 'Não autenticado.']);
        exit;
    }
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado.']);
    exit;
}

$bancada_id = isset($_POST['bancada_id']) ? (int) $_POST['bancada_id'] : 0;
$produtos_json = isset($_POST['produtos_json']) ? trim((string) $_POST['produtos_json']) : '';

if ($bancada_id <= 0) {
    echo json_encode(['ok' => false, 'err' => 'Bancada inválida.']);
    exit;
}

$bancada = hidroponiaValidarBancadaUsuario($mysqli, (int) $user_id, $bancada_id);
if (!$bancada) {
    echo json_encode(['ok' => false, 'err' => 'Bancada não encontrada ou sem permissão.']);
    exit;
}

$items = json_decode($produtos_json, true);
if (!is_array($items) || !$items) {
    echo json_encode(['ok' => false, 'err' => 'Informe ao menos um produto cultivado.']);
    exit;
}

$area_total = (float) ($bancada['area_m2'] ?? 0);
$sanitized = [];

foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }
    $pid = (int) ($item['produto_id'] ?? $item['id'] ?? 0);
    if ($pid <= 0) {
        continue;
    }
    $sanitized[] = [
        'produto_id' => $pid,
        'area_m2' => isset($item['area_m2']) ? (float) $item['area_m2'] : 0.0,
        'percentual' => isset($item['percentual']) ? (float) $item['percentual'] : 0.0,
    ];
}

if (!$sanitized) {
    echo json_encode(['ok' => false, 'err' => 'Selecione produtos válidos.']);
    exit;
}

try {
    $mysqli->begin_transaction();
    hidroponiaSalvarProdutosBancadaDetalhe($mysqli, $bancada_id, $sanitized, $area_total);
    $mysqli->commit();

    $produtos = hidroponiaListarProdutosBancada(
        $mysqli,
        $bancada_id,
        (int) ($sanitized[0]['produto_id'] ?? 0),
        $area_total
    );

    echo json_encode([
        'ok' => true,
        'bancada_id' => $bancada_id,
        'produtos' => $produtos,
        'cultura' => hidroponiaFormatCulturas($produtos),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => caderno_erro_msg($e)]);
}
