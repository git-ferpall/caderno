<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../funcoes/fitossanitaria/lote.php';

$codigo = trim((string) ($_GET['codigo'] ?? ''));
$hash = trim((string) ($_GET['hash'] ?? ''));

$lote = null;
if ($codigo !== '') {
    $lote = fsBuscarLotePorCodigo($mysqli, $codigo, $hash !== '' ? $hash : null);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de lote — Caderno Frutag</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
    <style>
        .ver-lote-page { max-width: 520px; margin: 0 auto; padding: 40px 20px 60px; text-align: left; }
        .ver-lote-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 8px 32px rgba(0,0,0,.1); }
        .ver-lote-ok { color: #2e7d32; font-weight: 700; }
        .ver-lote-fail { color: #c62828; font-weight: 700; }
        .ver-lote-hash { font-family: monospace; font-size: 11px; word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 8px; }
        .ver-lote-meta { color: #666; font-size: 14px; margin: 8px 0; }
    </style>
</head>
<body>
    <div class="ver-lote-page">
        <div class="ver-lote-card">
            <h1 style="font-size:1.25rem;margin:0 0 12px;color:#2e6b30">Verificação de lote Fitossanitário</h1>

            <?php if (!$lote): ?>
                <p class="ver-lote-fail">Lote não encontrado ou hash inválido.</p>
                <p class="ver-lote-meta">Confira o QR Code ou o código informado no relatório.</p>
            <?php else: ?>
                <?php if ($hash !== '' && !($lote['integridade_ok'] ?? false)): ?>
                    <p class="ver-lote-fail">Hash não confere — registro pode ter sido alterado.</p>
                <?php else: ?>
                    <p class="ver-lote-ok">✓ Registro encontrado<?= $hash !== '' ? ' e hash válido' : '' ?></p>
                <?php endif; ?>

                <p class="ver-lote-meta"><strong>Lote:</strong> <?= htmlspecialchars($lote['codigo_lote']) ?></p>
                <p class="ver-lote-meta"><strong>Propriedade:</strong> <?= htmlspecialchars($lote['propriedade_nome']) ?></p>
                <p class="ver-lote-meta"><strong>Área:</strong> <?= htmlspecialchars($lote['area_nome']) ?></p>
                <p class="ver-lote-meta"><strong>Score:</strong> <?= htmlspecialchars($lote['score_nivel']) ?></p>
                <p class="ver-lote-meta"><strong>Status:</strong> <?= htmlspecialchars($lote['status_lote']) ?></p>
                <p class="ver-lote-meta"><strong>Atualizado:</strong> <?= htmlspecialchars($lote['atualizado_em']) ?></p>

                <p class="ver-lote-hash"><?= htmlspecialchars($lote['hash_auditoria']) ?></p>

                <?php
                $url = fsUrlVerificacaoLote($lote['codigo_lote'], $lote['hash_auditoria']);
                $qr = fsUrlQrCode($url, 160);
                ?>
                <p style="text-align:center;margin-top:16px">
                    <img src="<?= htmlspecialchars($qr) ?>" width="160" height="160" alt="QR Code auditoria">
                </p>
            <?php endif; ?>

            <p style="margin-top:20px;font-size:12px;color:#888">
                Ferramenta de apoio à rastreabilidade. Decisão final cabe ao responsável técnico habilitado.
            </p>
        </div>
    </div>
</body>
</html>
