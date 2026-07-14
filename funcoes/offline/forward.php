<?php
declare(strict_types=1);

/**
 * Repete salvamento da fila offline com idempotência (client_id).
 */
require_once __DIR__ . '/sync_guard.php';

$user_id = offlineAuthUserId();
if (!$user_id) {
    offlineJson(['ok' => false, 'err' => 'Não autenticado.'], 401);
}

$script = basename((string)($_POST['_offline_script'] ?? ''));
$allowed = offlineSalvarEndpoints();
if ($script === '' || !in_array($script, $allowed, true)) {
    offlineJson(['ok' => false, 'err' => 'Tipo de apontamento inválido.'], 400);
}

$client_id = trim((string)($_POST['client_id'] ?? ''));
if ($client_id !== '' && offlineSyncFindDuplicate($mysqli, (int)$user_id, $client_id)) {
    offlineJson([
        'ok' => true,
        'msg' => 'Apontamento já sincronizado.',
        'duplicate' => true,
    ]);
}

unset($_POST['_offline_script']);

$base = realpath(__DIR__ . '/..');
$target = realpath(__DIR__ . '/../' . $script);
if (!$base || !$target || !str_starts_with($target, $base) || !is_file($target)) {
    offlineJson(['ok' => false, 'err' => 'Destino não encontrado.'], 404);
}

ob_start();
try {
    include $target;
} catch (Throwable $e) {
    ob_end_clean();
    offlineJson(['ok' => false, 'err' => caderno_erro_msg($e)], 500);
}
$out = ob_get_clean();

if ($client_id !== '') {
    $data = json_decode($out, true);
    if (is_array($data) && !empty($data['ok'])) {
        offlineSyncRegister($mysqli, (int)$user_id, $client_id, $script);
    }
}

echo $out;
