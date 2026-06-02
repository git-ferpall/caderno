<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$user_id = offlineAuthUserId();
if (!$user_id) {
    offlineJson(['ok' => false, 'habilitado' => false, 'is_admin' => false], 401);
}

$is_admin = offlineIsAdmin($mysqli, $user_id);
$habilitado = offlineIsEnabled($mysqli, $user_id);

$nome = null;
$stmt = $mysqli->prepare("SELECT nome FROM contato_cliente WHERE user_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $nome = $row['nome'] ?? null;
    $stmt->close();
}

$session_days = defined('OFFLINE_SESSION_DAYS') ? (int)OFFLINE_SESSION_DAYS : 30;

offlineJson([
    'ok' => true,
    'habilitado' => $habilitado,
    'is_admin' => $is_admin,
    'user_id' => $user_id,
    'nome' => $nome,
    'session_days' => $session_days,
    'online' => true,
]);
