<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/vinculo.php';
require_once __DIR__ . '/../ia/pipeline.php';

function waProcessarMensagem(mysqli $mysqli, array $message): void
{
    $waId = (string) ($message['from'] ?? '');
    if ($waId === '') {
        return;
    }

    $type = (string) ($message['type'] ?? '');

    if ($type === 'text') {
        $texto = trim((string) ($message['text']['body'] ?? ''));
        waProcessarTexto($mysqli, $waId, $texto);
        return;
    }

    if ($type === 'audio') {
        $mediaId = (string) ($message['audio']['id'] ?? '');
        if ($mediaId === '') {
            waSendText($waId, 'Não recebi o áudio. Tente enviar novamente.');
            return;
        }
        waProcessarAudio($mysqli, $waId, $mediaId);
        return;
    }

    if ($type === 'interactive') {
        $reply = $message['interactive']['button_reply']['title']
            ?? $message['interactive']['list_reply']['title']
            ?? '';
        if ($reply !== '') {
            waProcessarTexto($mysqli, $waId, trim($reply));
        }
        return;
    }

    waSendText($waId, 'Envie um *áudio* ou *texto* com o manejo. Digite *AJUDA* para ver exemplos.');
}

function waProcessarTexto(mysqli $mysqli, string $waId, string $texto): void
{
    if ($texto === '') {
        return;
    }

    $cmd = mb_strtoupper($texto);

    if ($cmd === 'AJUDA' || $cmd === 'HELP') {
        waSendText($waId, waHelpMessage());
        return;
    }

    if ($cmd === 'VINCULAR' || $cmd === 'VINCULO' || $cmd === 'VÍNCULO') {
        $vinculo = waResolverUsuario($mysqli, $waId);
        if ($vinculo) {
            waSendText($waId, "✅ Número vinculado! Pode enviar áudio ou texto com o manejo.\n\nDigite *AJUDA* para exemplos.");
        } else {
            waSendText($waId, waMensagemNaoVinculado());
        }
        return;
    }

    $sessao = waCarregarSessao($mysqli, $waId);
    if ($sessao) {
        if (($sessao['modo'] ?? '') === 'dialogo') {
            waContinuarDialogo($mysqli, $waId, $sessao, $texto);
            return;
        }
        if (waIsSim($texto)) {
            waConfirmarSessao($mysqli, $waId, $sessao);
            return;
        }
        if (waIsNao($texto)) {
            waLimparSessao($mysqli, $waId);
            waSendText($waId, 'Ok, cancelado. Pode gravar ou digitar outro comando.');
            return;
        }
        waSendText($waId, 'Há uma confirmação pendente. Responda *SIM* ou *NÃO*.');
        return;
    }

    $vinculo = waResolverUsuario($mysqli, $waId);
    if (!$vinculo) {
        waSendText($waId, waMensagemNaoVinculado());
        return;
    }

    try {
        $pipeline = new IaPipeline($mysqli, (int) $vinculo['user_id']);
        $resultado = $pipeline->processFromText($texto);
        waResponderPipeline($mysqli, $waId, (int) $vinculo['user_id'], $resultado);
    } catch (Throwable $e) {
        waLog('texto: ' . $e->getMessage());
        waSendText($waId, '❌ ' . $e->getMessage());
    }
}

function waProcessarAudio(mysqli $mysqli, string $waId, string $mediaId): void
{
    $sessao = waCarregarSessao($mysqli, $waId);
    if ($sessao && ($sessao['modo'] ?? '') === 'confirmar') {
        waSendText($waId, 'Há uma confirmação pendente. Responda *SIM* ou *NÃO* antes de enviar outro áudio.');
        return;
    }

    $vinculo = waResolverUsuario($mysqli, $waId);
    if (!$vinculo) {
        waSendText($waId, waMensagemNaoVinculado());
        return;
    }

    $tmp = tempnam(sys_get_temp_dir(), 'wa_audio_');
    if ($tmp === false) {
        waSendText($waId, 'Erro interno ao processar áudio.');
        return;
    }

    try {
        waSendText($waId, '🎤 Processando seu áudio…');

        $media = waDownloadMedia($mediaId);
        file_put_contents($tmp, $media['binary']);

        $pipeline = new IaPipeline($mysqli, (int) $vinculo['user_id']);

        if ($sessao && ($sessao['modo'] ?? '') === 'dialogo') {
            $resultado = $pipeline->processFromAudio(
                $tmp,
                $media['mime'],
                $sessao['intent'],
                $sessao['campo_dialogo'] ?: null
            );
        } else {
            $resultado = $pipeline->processFromAudio($tmp, $media['mime']);
        }

        waResponderPipeline($mysqli, $waId, (int) $vinculo['user_id'], $resultado);
    } catch (Throwable $e) {
        waLog('audio: ' . $e->getMessage());
        waSendText($waId, '❌ ' . $e->getMessage());
    } finally {
        @unlink($tmp);
    }
}

function waContinuarDialogo(mysqli $mysqli, string $waId, array $sessao, string $texto): void
{
    if (waIsNao($texto)) {
        waLimparSessao($mysqli, $waId);
        waSendText($waId, 'Ok, cancelado. Envie outro comando quando quiser.');
        return;
    }

    try {
        $pipeline = new IaPipeline($mysqli, (int) $sessao['user_id']);
        $resultado = $pipeline->processFromText(
            $texto,
            $texto,
            $sessao['intent'],
            $sessao['campo_dialogo'] ?: null
        );
        waResponderPipeline($mysqli, $waId, (int) $sessao['user_id'], $resultado);
    } catch (Throwable $e) {
        waLog('dialogo: ' . $e->getMessage());
        waSendText($waId, '❌ ' . $e->getMessage());
    }
}

function waConfirmarSessao(mysqli $mysqli, string $waId, array $sessao): void
{
    try {
        $pipeline = new IaPipeline($mysqli, (int) $sessao['user_id']);
        $resultado = $pipeline->executeIntent($sessao['intent'], $sessao['resolucao']);
        waLimparSessao($mysqli, $waId);

        if ($resultado['ok'] ?? false) {
            waSendText($waId, '✅ ' . ($resultado['msg'] ?? 'Registrado com sucesso!'));
        } else {
            waSendText($waId, '❌ ' . ($resultado['msg'] ?? 'Não foi possível executar.'));
        }
    } catch (Throwable $e) {
        waLog('confirmar: ' . $e->getMessage());
        waLimparSessao($mysqli, $waId);
        waSendText($waId, '❌ ' . $e->getMessage());
    }
}

function waResponderPipeline(mysqli $mysqli, string $waId, int $user_id, array $resultado): void
{
    $transcricao = trim((string) ($resultado['transcricao'] ?? ''));
    $resumo = trim((string) ($resultado['resumo'] ?? ''));
    $msg = trim((string) ($resultado['msg'] ?? ''));

    if ($resultado['executado'] ?? false) {
        waLimparSessao($mysqli, $waId);
        $texto = '✅ ' . ($msg ?: 'Registrado com sucesso!');
        if ($transcricao !== '') {
            $texto .= "\n\n📝 _" . $transcricao . '_';
        }
        waSendText($waId, $texto);
        return;
    }

    if ($resultado['precisa_dialogo'] ?? false) {
        $intent = $resultado['intent'] ?? [];
        $resolucao = $resultado['resolucao'] ?? [];
        $campo = (string) ($resultado['campo_dialogo'] ?? '');
        $pergunta = (string) ($resultado['pergunta'] ?? $msg);
        waSalvarSessao($mysqli, $waId, $user_id, $intent, $resolucao, $resumo, 30, 'dialogo', $campo ?: null);
        waSendText($waId, '❓ ' . $pergunta);
        return;
    }

    if ($resultado['precisa_confirmacao'] ?? false) {
        waLimparSessao($mysqli, $waId);
        $intent = $resultado['intent'] ?? [];
        $resolucao = $resultado['resolucao'] ?? [];
        waSalvarSessao($mysqli, $waId, $user_id, $intent, $resolucao, $resumo, 30, 'confirmar');

        $texto = "⚠️ *Confirme o manejo:*\n" . $resumo;
        if ($transcricao !== '' && $transcricao !== $resumo) {
            $texto .= "\n\n📝 Você disse: _" . $transcricao . '_';
        }
        $texto .= "\n\nResponda *SIM* para confirmar ou *NÃO* para cancelar.";
        waSendText($waId, $texto);
        return;
    }

    waSendText($waId, '❌ ' . ($msg ?: 'Não entendi. Tente reformular ou digite AJUDA.'));
}

function waProcessarWebhookPayload(mysqli $mysqli, array $payload): void
{
    if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
        return;
    }

    foreach ($payload['entry'] ?? [] as $entry) {
        foreach ($entry['changes'] ?? [] as $change) {
            $value = $change['value'] ?? [];
            if (($value['messaging_product'] ?? '') !== 'whatsapp') {
                continue;
            }

            foreach ($value['messages'] ?? [] as $message) {
                try {
                    waProcessarMensagem($mysqli, $message);
                } catch (Throwable $e) {
                    waLog('msg: ' . $e->getMessage());
                    $from = (string) ($message['from'] ?? '');
                    if ($from !== '') {
                        try {
                            waSendText($from, '❌ Ocorreu um erro ao processar. Tente novamente em instantes.');
                        } catch (Throwable) {
                            /* ignore */
                        }
                    }
                }
            }
        }
    }
}
