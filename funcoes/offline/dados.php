<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$user_id = offlineAuthUserId();
if (!$user_id) {
    offlineJson(['ok' => false, 'msg' => 'Não autenticado.'], 401);
}

if (!offlineIsEnabled($mysqli, $user_id)) {
    offlineJson(['ok' => false, 'msg' => 'Offline não habilitado para este usuário.'], 403);
}

$propriedade_id = null;
$propriedade_nome = null;

$stmt = $mysqli->prepare("SELECT id, nome_razao FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$prop = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($prop) {
    $propriedade_id = (int)$prop['id'];
    $propriedade_nome = $prop['nome_razao'];
}

$areas = [];
$produtos = [];
$maquinas = [];

if ($propriedade_id) {
    $stmt = $mysqli->prepare("SELECT id, nome, tipo FROM areas WHERE user_id = ? AND propriedade_id = ? ORDER BY nome ASC");
    $stmt->bind_param('ii', $user_id, $propriedade_id);
    $stmt->execute();
    $areas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$stmt = $mysqli->prepare("SELECT id, nome FROM produtos WHERE user_id = ? ORDER BY nome ASC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$produtos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $mysqli->prepare("SELECT id, nome, tipo FROM maquinas WHERE user_id = ? ORDER BY nome ASC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$maquinas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$herbicidas = $mysqli->query("SELECT id, nome FROM herbicidas WHERE status = 'ativo' ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC) ?: [];
$fertilizantes = $mysqli->query("SELECT id, nome FROM fertilizantes WHERE status = 'ativo' ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC) ?: [];
$fungicidas = $mysqli->query("SELECT id, nome FROM fungicidas WHERE ativo = 1 ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC) ?: [];
$inseticidas = $mysqli->query("SELECT id, nome FROM inseticidas WHERE ativo = 1 ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC) ?: [];

offlineJson([
    'ok' => true,
    'atualizado_em' => date('c'),
    'propriedade' => [
        'id' => $propriedade_id,
        'nome' => $propriedade_nome,
    ],
    'areas' => $areas,
    'produtos' => $produtos,
    'maquinas' => $maquinas,
    'herbicidas' => $herbicidas,
    'fertilizantes' => $fertilizantes,
    'fungicidas' => $fungicidas,
    'inseticidas' => $inseticidas,
]);
