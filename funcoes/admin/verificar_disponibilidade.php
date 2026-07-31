<?php
declare(strict_types=1);

/**
 * Verifica se um login ou e-mail está disponível para um novo usuário local.
 * GET ?valor=maria.silva  →  { ok: true, disponivel: true|false }
 */

require_once __DIR__ . '/helpers.php';

adminRequirePerfil($mysqli, ['admin', 'representante']);

$valor = strtolower(trim((string)($_GET['valor'] ?? '')));
if ($valor === '') {
    adminJson(['ok' => false, 'msg' => 'Informe um valor para verificar.'], 400);
}

// disponível somente se estiver livre no Caderno E na integração Frutag
$status = usuarioCredencialStatus($mysqli, $valor);

adminJson([
    'ok'         => true,
    'disponivel' => $status['disponivel'],
    'origem'     => $status['origem'],
]);
