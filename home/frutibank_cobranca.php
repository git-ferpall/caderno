<?php
// Acesso público via token (?t=...): link que o recebedor envia ao cliente
// pelo WhatsApp. Sem token, exige login e ser o dono da cobrança.
$fbToken = trim($_GET['t'] ?? '');
$fbPublico = $fbToken !== '' && preg_match('/^[a-f0-9]{32}$/', $fbToken);

if (!$fbPublico) {
    require_once __DIR__ . '/../configuracao/protect.php';
} else {
    require_once __DIR__ . '/../configuracao/https.php';
}
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../funcoes/frutibank/helpers.php';

$sqlBase = "
    SELECT fc.*, c.nome AS cliente_nome, c.cpf_cnpj AS cliente_doc, c.telefone AS cliente_telefone,
           cfg.chave_pix, cfg.tipo_chave, cfg.nome_recebedor, cfg.cidade, cfg.uf
    FROM frutibank_cobrancas fc
    JOIN frutibank_clientes c ON c.id = fc.cliente_id
    LEFT JOIN frutibank_config cfg ON cfg.user_id = fc.user_id
";

if ($fbPublico) {
    frutibankEnsureSchema($mysqli);
    $stmt = $mysqli->prepare($sqlBase . ' WHERE fc.token = ? LIMIT 1');
    $stmt->bind_param('s', $fbToken);
} else {
    $fbUser = $GLOBALS['auth_user'] ?? null;
    $fbUserId = (int)($fbUser->sub ?? 0);
    $cobrancaId = (int)($_GET['id'] ?? 0);

    if (!$fbUserId || !frutibankHabilitado($mysqli, $fbUserId)) {
        http_response_code(403);
        exit('Acesso negado.');
    }

    $stmt = $mysqli->prepare($sqlBase . ' WHERE fc.id = ? AND fc.user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $cobrancaId, $fbUserId);
}
$stmt->execute();
$cob = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cob) {
    http_response_code(404);
    exit('Cobrança não encontrada.');
}

function fbDoc(string $doc): string
{
    if (strlen($doc) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
    }
    if (strlen($doc) === 14) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
    }
    return $doc;
}

$valorFmt = 'R$ ' . number_format((float)$cob['valor'], 2, ',', '.');
$emissao = date('d/m/Y', strtotime($cob['criado_em']));
$vencimento = $cob['vencimento'] ? date('d/m/Y', strtotime($cob['vencimento'])) : '—';
$payload = (string)$cob['payload'];

$linkPublico = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'caderno.frutag.com.br') . '/home/frutibank_cobranca?t=' . $cob['token'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cobrança PIX #<?= (int)$cob['id'] ?> — Frutibank</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body class="fb-doc-body">
    <div class="fb-doc-toolbar no-print">
        <?php if (!$fbPublico): ?>
        <a href="/home/frutibank" class="main-btn fundo-preto">Voltar</a>
        <?php endif; ?>
        <button type="button" class="main-btn fundo-azul" onclick="window.print()">Imprimir</button>
        <button type="button" class="main-btn fundo-verde" id="fb-copiar">Copiar código PIX</button>
        <?php if (!$fbPublico): ?>
        <button type="button" class="main-btn fb-btn-whats" id="fb-whatsapp">Enviar por WhatsApp</button>
        <?php endif; ?>
    </div>

    <div class="fb-doc">
        <header class="fb-doc-header">
            <div class="fb-doc-brand">
                <img src="../img/logo-color.png" alt="Caderno de Campo Frutag">
                <div>
                    <strong>FRUTIBANK</strong>
                    <span>Cobrança via PIX</span>
                </div>
            </div>
            <div class="fb-doc-num">
                <span>Cobrança</span>
                <strong>#<?= str_pad((string)$cob['id'], 6, '0', STR_PAD_LEFT) ?></strong>
            </div>
        </header>

        <div class="fb-doc-grid">
            <div class="fb-doc-campo fb-span-2">
                <label>Beneficiário (recebedor)</label>
                <strong><?= htmlspecialchars($cob['nome_recebedor'] ?? '') ?></strong>
            </div>
            <div class="fb-doc-campo">
                <label>Chave PIX (<?= htmlspecialchars(strtoupper($cob['tipo_chave'] ?? '')) ?>)</label>
                <strong><?= htmlspecialchars($cob['chave_pix'] ?? '') ?></strong>
            </div>
            <div class="fb-doc-campo">
                <label>Cidade</label>
                <strong><?= htmlspecialchars(trim(($cob['cidade'] ?? '') . (!empty($cob['uf']) ? '/' . $cob['uf'] : ''))) ?></strong>
            </div>

            <div class="fb-doc-campo fb-span-2">
                <label>Pagador</label>
                <strong><?= htmlspecialchars($cob['cliente_nome']) ?></strong>
            </div>
            <div class="fb-doc-campo">
                <label><?= strlen($cob['cliente_doc']) === 14 ? 'CNPJ' : 'CPF' ?></label>
                <strong><?= htmlspecialchars(fbDoc($cob['cliente_doc'])) ?></strong>
            </div>
            <div class="fb-doc-campo">
                <label>Identificador (TXID)</label>
                <strong class="fb-txid"><?= htmlspecialchars($cob['txid']) ?></strong>
            </div>

            <div class="fb-doc-campo">
                <label>Data de emissão</label>
                <strong><?= $emissao ?></strong>
            </div>
            <div class="fb-doc-campo">
                <label>Vencimento</label>
                <strong><?= $vencimento ?></strong>
            </div>
            <div class="fb-doc-campo fb-span-2 fb-doc-valor">
                <label>Valor a pagar</label>
                <strong><?= $valorFmt ?></strong>
            </div>

            <?php if (!empty($cob['descricao'])): ?>
            <div class="fb-doc-campo fb-span-4">
                <label>Descrição</label>
                <strong><?= htmlspecialchars($cob['descricao']) ?></strong>
            </div>
            <?php endif; ?>
        </div>

        <div class="fb-doc-pagamento">
            <div class="fb-doc-instrucoes">
                <h2>Como pagar</h2>
                <ol>
                    <li>Abra o aplicativo do seu banco;</li>
                    <li>Escolha pagar com <strong>PIX &rarr; Ler QR Code</strong>;</li>
                    <li>Aponte a câmera para o código ao lado;</li>
                    <li>Confira o nome do recebedor e o valor de <strong><?= $valorFmt ?></strong>;</li>
                    <li>Confirme o pagamento.</li>
                </ol>
                <p class="fb-doc-alt">Ou use o <strong>PIX copia-e-cola</strong> abaixo, colando o código na opção "PIX copia e cola" do seu banco.</p>
            </div>
            <div class="fb-doc-qr">
                <div id="fb-qrcode"></div>
                <span>Pague com PIX</span>
            </div>
        </div>

        <div class="fb-doc-copia">
            <label>PIX copia-e-cola</label>
            <div class="fb-doc-copia-box" id="fb-payload"><?= htmlspecialchars($payload) ?></div>
        </div>

        <div class="fb-doc-barcode">
            <svg id="fb-barcode"></svg>
        </div>

        <footer class="fb-doc-footer">
            <p>Documento gerado pelo Frutibank — Caderno de Campo Frutag. Esta é uma cobrança via PIX (não é um boleto bancário registrado). Em caso de dúvida, confirme os dados com o beneficiário antes de pagar.</p>
        </footer>
    </div>

    <script src="../js/vendor/qrcodejs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        const fbPayload = document.getElementById("fb-payload").textContent.trim();

        new QRCode(document.getElementById("fb-qrcode"), {
            text: fbPayload,
            width: 210,
            height: 210,
            correctLevel: QRCode.CorrectLevel.M,
        });

        try {
            JsBarcode("#fb-barcode", fbPayload, {
                format: "CODE128",
                displayValue: false,
                height: 70,
                margin: 0,
            });
        } catch (e) {
            document.querySelector(".fb-doc-barcode").style.display = "none";
        }

        const btnWhats = document.getElementById("fb-whatsapp");
        btnWhats?.addEventListener("click", () => {
            const telefone = <?= json_encode(preg_replace('/\D/', '', (string)($cob['cliente_telefone'] ?? ''))) ?>;
            const texto = <?= json_encode(
                "Olá, {$cob['cliente_nome']}!\n\n"
                . "Segue a cobrança PIX de {$valorFmt}"
                . ($cob['vencimento'] ? " com vencimento em {$vencimento}" : '')
                . ($cob['descricao'] ? " — {$cob['descricao']}" : '')
                . ".\n\nVeja a cobrança completa (QR Code, impressão e código PIX copia-e-cola):\n{$linkPublico}\n\n"
                . "Ou pague agora copiando o código PIX abaixo e colando na opção \"PIX copia e cola\" do seu banco:\n\n{$payload}",
                JSON_UNESCAPED_UNICODE
            ) ?>;
            const destino = telefone ? `https://wa.me/${telefone.length <= 11 ? "55" + telefone : telefone}` : "https://wa.me/";
            window.open(`${destino}?text=${encodeURIComponent(texto)}`, "_blank", "noopener");
        });

        const btnCopiar = document.getElementById("fb-copiar");
        btnCopiar?.addEventListener("click", async () => {
            try {
                await navigator.clipboard.writeText(fbPayload);
                const original = btnCopiar.textContent;
                btnCopiar.textContent = "Copiado!";
                btnCopiar.disabled = true;
                setTimeout(() => {
                    btnCopiar.textContent = original;
                    btnCopiar.disabled = false;
                }, 2200);
            } catch (e) {
                prompt("Copie o código PIX abaixo:", fbPayload);
            }
        });
    </script>
</body>
</html>
