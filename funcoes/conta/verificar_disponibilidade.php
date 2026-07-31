<?php
declare(strict_types=1);

/**
 * Verifica se um login ou e-mail está disponível para um novo acesso da conta.
 * GET ?valor=joao.silva  →  { ok: true, disponivel: true|false }
 */

require_once __DIR__ . '/helpers.php';

contaRequireGestao();

$valor = strtolower(trim((string)($_GET['valor'] ?? '')));
if ($valor === '') {
    contaJson(['ok' => false, 'msg' => 'Informe um valor para verificar.'], 400);
}

contaJson([
    'ok'         => true,
    'disponivel' => usuarioCredencialDisponivel($mysqli, $valor),
]);
