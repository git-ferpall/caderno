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
$livreLocal  = usuarioCredencialDisponivel($mysqli, $valor);
$livreFrutag = $livreLocal ? usuarioCredencialDisponivelFrutag($valor) : true;

adminJson([
    'ok'         => true,
    'disponivel' => $livreLocal && $livreFrutag,
    'origem'     => !$livreLocal ? 'caderno' : (!$livreFrutag ? 'frutag' : null),
]);
