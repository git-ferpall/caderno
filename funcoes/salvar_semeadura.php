<?php
declare(strict_types=1);

require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

function semeaduraJsonError(string $msg, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'err' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    semeaduraJsonError('Usuário não autenticado.', 401);
}

$stmt = $mysqli->prepare('SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$prop = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prop) {
    semeaduraJsonError('Nenhuma propriedade ativa encontrada.');
}

$propriedade_id = (int) $prop['id'];

$data = trim((string) ($_POST['data'] ?? ''));
$areas = array_values(array_filter(array_map('intval', (array) ($_POST['area'] ?? []))));
$produtos = array_values(array_filter(array_map('intval', (array) ($_POST['produto'] ?? []))));
$variedade = trim((string) ($_POST['variedade'] ?? ''));
$tipoSemeadura = trim((string) ($_POST['tipo_semeadura'] ?? ''));
$quantidade = isset($_POST['quantidade']) && $_POST['quantidade'] !== '' ? (float) $_POST['quantidade'] : null;
$unidade = trim((string) ($_POST['unidade'] ?? ''));
$obs = trim((string) ($_POST['obs'] ?? ''));

$tiposValidos = ['Direta', 'Bandeja', 'Canteiro', 'Replantio'];

if ($data === '' || !$areas || !$produtos || $tipoSemeadura === '') {
    semeaduraJsonError('Preencha data, área, produto e tipo de semeadura.');
}

if (!in_array($tipoSemeadura, $tiposValidos, true)) {
    semeaduraJsonError('Tipo de semeadura inválido.');
}

if ($quantidade === null || $quantidade <= 0) {
    semeaduraJsonError('Informe a quantidade semeada.');
}

if ($unidade === '') {
    semeaduraJsonError('Informe a unidade da quantidade.');
}

$mysqli->begin_transaction();

try {
    $tipo = 'semeadura';
    $status = 'concluido';

    $stmt = $mysqli->prepare('
        INSERT INTO apontamentos
        (propriedade_id, tipo, data, quantidade, unidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->bind_param(
        'issdsss',
        $propriedade_id,
        $tipo,
        $data,
        $quantidade,
        $unidade,
        $obs,
        $status
    );
    $stmt->execute();
    $apontamento_id = (int) $stmt->insert_id;
    $stmt->close();

    if (!$apontamento_id) {
        throw new RuntimeException('Falha ao criar apontamento.');
    }

    $stmtDet = $mysqli->prepare('
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, ?, ?)
    ');

    foreach ($areas as $area_id) {
        $campo = 'area_id';
        $valor = (string) $area_id;
        $stmtDet->bind_param('iss', $apontamento_id, $campo, $valor);
        $stmtDet->execute();
    }

    foreach ($produtos as $produto_id) {
        $campo = 'produto_id';
        $valor = (string) $produto_id;
        $stmtDet->bind_param('iss', $apontamento_id, $campo, $valor);
        $stmtDet->execute();
    }

    $extras = [
        'variedade' => $variedade,
        'tipo_semeadura' => $tipoSemeadura,
    ];

    foreach ($extras as $campo => $valor) {
        if ($valor === '') {
            continue;
        }
        $stmtDet->bind_param('iss', $apontamento_id, $campo, $valor);
        $stmtDet->execute();
    }

    $stmtDet->close();
    $mysqli->commit();

    echo json_encode([
        'ok' => true,
        'msg' => 'Semeadura registrada com sucesso!',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $mysqli->rollback();
    semeaduraJsonError($e->getMessage(), 500);
}
