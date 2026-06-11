<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/ia_helpers.php';
require_once __DIR__ . '/pipeline.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    iaJson(['ok' => false, 'err' => 'Método não permitido'], 405);
}

$user_id = iaAuthUserId();

if (empty($_FILES['audio']['tmp_name'])) {
    iaJson(['ok' => false, 'err' => 'Envie um arquivo de áudio.'], 400);
}

$tmp = $_FILES['audio']['tmp_name'];
$mime = $_FILES['audio']['type'] ?: 'audio/webm';
$size = (int) ($_FILES['audio']['size'] ?? 0);

if ($size <= 0 || $size > 25 * 1024 * 1024) {
    iaJson(['ok' => false, 'err' => 'Áudio inválido ou muito grande (máx. 25 MB).'], 400);
}

try {
    $pipeline = new IaPipeline($mysqli, $user_id);
    iaJson($pipeline->processFromAudio($tmp, $mime));
} catch (Throwable $e) {
    iaJson(['ok' => false, 'err' => $e->getMessage()], 500);
}
