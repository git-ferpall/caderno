<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/ia_helpers.php';
require_once __DIR__ . '/briefing.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    iaJson(['ok' => false, 'err' => 'Método não permitido'], 405);
}

$user_id = iaAuthUserId();

try {
    $msg = iaGerarBriefing($mysqli, $user_id);
    iaJson(['ok' => true, 'msg' => $msg, 'fala' => $msg]);
} catch (Throwable $e) {
    iaJson(['ok' => false, 'err' => caderno_erro_msg($e)], 500);
}
