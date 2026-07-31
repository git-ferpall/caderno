<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

[$contaId, $papel, $funcId] = contaRequireGestao();

$stmt = $mysqli->prepare("
    SELECT id, login, email, nome, papel_conta, ativo, criado_em
    FROM usuarios_caderno
    WHERE conta_pai = ?
    ORDER BY nome ASC
");
$stmt->bind_param('i', $contaId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$config = contaFuncConfig($mysqli, $contaId);

contaJson([
    'ok'        => true,
    'papel'     => $papel,
    'func_id'   => $funcId,
    'liberado'  => $config !== null,
    'limite'    => $config !== null ? (int)$config['limite'] : 0,
    'usuarios'  => $rows,
]);
