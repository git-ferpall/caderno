<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/ia_helpers.php';
require_once __DIR__ . '/acoes_rapidas.php';
require_once __DIR__ . '/pipeline.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    iaJson(['ok' => false, 'err' => 'Método não permitido'], 405);
}

$user_id = iaAuthUserId();
$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '{}', true);
if (!is_array($payload)) {
    iaJson(['ok' => false, 'err' => 'JSON inválido'], 400);
}

$intentParcial = null;
$campoDialogo = trim((string) ($payload['campo_dialogo'] ?? ''));

if (!empty($payload['intent_parcial']) && is_array($payload['intent_parcial'])) {
    $intentParcial = iaSanitizarIntentParcial($payload['intent_parcial']);
}

try {
    $pipeline = new IaPipeline($mysqli, $user_id);

    if (!empty($payload['acao_rapida']) && is_array($payload['acao_rapida'])) {
        $intent = iaMapAcaoRapida($payload['acao_rapida']);
        $rotulo = trim((string) ($payload['texto'] ?? ''));
        iaJson($pipeline->processFromIntent($intent, $rotulo !== '' ? $rotulo : 'Ação no card'));
        exit;
    }

    $texto = trim((string) ($payload['texto'] ?? ''));
    if ($texto === '' && $intentParcial === null) {
        iaJson(['ok' => false, 'err' => 'Envie o texto do comando.'], 400);
    }

    iaJson($pipeline->processFromText($texto, $texto, $intentParcial, $campoDialogo ?: null));
} catch (Throwable $e) {
    iaJson(['ok' => false, 'err' => $e->getMessage()], 500);
}
