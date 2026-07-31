<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

[$contaId] = contaRequireGestao();

contaJson([
    'ok'           => true,
    'propriedades' => contaListarPropriedades($mysqli, $contaId),
]);
