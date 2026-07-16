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
$cidadeUf = trim(($cob['cidade'] ?? '') . (!empty($cob['uf']) ? '/' . $cob['uf'] : ''));

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
    <?php if (!$fbPublico): ?>
    <div class="fb-doc-topbar no-print">
        <a href="/home/frutibank#cobrancas" class="fb-doc-voltar">&larr; Voltar às cobranças</a>
    </div>
    <?php endif; ?>

    <div class="fb-doc">
        <header class="fb-doc-header">
            <img src="../img/frutibank-logo.png" alt="Frutibank" class="fb-doc-logo">
            <span class="fb-doc-badge">BOLETO PIX</span>
        </header>

        <div class="fb-doc-grid">
            <div class="fb-doc-campo fb-span-3">
                <label>Beneficiário (recebedor)</label>
                <strong><?= htmlspecialchars($cob['nome_recebedor'] ?? '') ?><?php if ($cidadeUf !== ''): ?> <span class="fb-doc-sub">&middot; <?= htmlspecialchars($cidadeUf) ?></span><?php endif; ?></strong>
            </div>
            <div class="fb-doc-campo fb-doc-chave">
                <label>Chave PIX (<?= htmlspecialchars(strtoupper($cob['tipo_chave'] ?? '')) ?>) <small class="no-print">— toque para copiar</small></label>
                <strong id="fb-chave-valor" role="button" tabindex="0" title="Clique para copiar a chave PIX"><?= htmlspecialchars($cob['chave_pix'] ?? '') ?></strong>
            </div>

            <div class="fb-doc-campo fb-span-3">
                <label>Pagador (sacado)</label>
                <strong><?= htmlspecialchars($cob['cliente_nome']) ?> <span class="fb-doc-sub">&middot; <?= htmlspecialchars(fbDoc($cob['cliente_doc'])) ?></span></strong>
            </div>
            <div class="fb-doc-campo">
                <label>Referência</label>
                <strong class="fb-txid"><?= htmlspecialchars($cob['txid']) ?></strong>
            </div>

            <div class="fb-doc-campo">
                <label>Emissão</label>
                <strong><?= $emissao ?></strong>
            </div>
            <div class="fb-doc-campo">
                <label>Vencimento</label>
                <strong><?= $vencimento ?></strong>
            </div>
            <div class="fb-doc-campo">
                <label>Descrição</label>
                <strong><?= !empty($cob['descricao']) ? htmlspecialchars($cob['descricao']) : '—' ?></strong>
            </div>
            <div class="fb-doc-campo fb-doc-valor">
                <label>Valor a pagar</label>
                <strong><?= $valorFmt ?></strong>
            </div>
        </div>

        <div class="fb-doc-pagamento">
            <div class="fb-doc-qr">
                <div id="fb-qrcode"></div>
            </div>
            <div class="fb-doc-copia">
                <label>PIX copia e cola <small class="no-print">— toque no código para copiar</small></label>
                <div class="fb-doc-copia-box" id="fb-payload" role="button" tabindex="0" title="Clique para copiar o código PIX"><?= htmlspecialchars($payload) ?></div>
                <div class="fb-doc-copia-acoes no-print">
                    <button type="button" class="fb-btn fb-btn-solid" id="fb-copiar">Copiar código PIX</button>
                    <?php if (!$fbPublico): ?>
                    <button type="button" class="fb-btn fb-btn-outline" id="fb-whatsapp">Enviar por WhatsApp</button>
                    <?php endif; ?>
                </div>
                <p class="fb-doc-instrucao">Abra o app do seu banco, escolha <strong>PIX &rarr; Ler QR Code</strong> ou <strong>PIX Copia e Cola</strong>. O valor já vai preenchido.</p>
            </div>
        </div>

        <div class="fb-doc-corte"><span>&#9986; recorte aqui</span></div>

        <div class="fb-doc-barcode">
            <label>Código de barras do PIX (CODE-128 &mdash; contém o mesmo código copia e cola)</label>
            <svg id="fb-barcode"></svg>
        </div>

        <footer class="fb-doc-footer">
            <p>Documento gerado pelo Frutibank — Caderno de Campo Frutag. Esta é uma cobrança via PIX (não é um boleto bancário registrado). Em caso de dúvida, confirme os dados com o beneficiário antes de pagar.</p>
        </footer>
    </div>

    <div class="fb-doc-toolbar no-print">
        <button type="button" class="fb-btn fb-btn-solid" onclick="window.print()">&#128424; Imprimir / salvar PDF</button>
        <?php if (!$fbPublico): ?>
        <button type="button" class="fb-btn fb-btn-outline" id="fb-marcar-pago" <?= $cob['status'] === 'pago' ? 'disabled' : '' ?>>
            <?= $cob['status'] === 'pago' ? '&#10003; Pago' : '&#10003; Marcar como pago' ?>
        </button>
        <?php endif; ?>
    </div>

    <script src="../js/vendor/qrcodejs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        const fbPayload = document.getElementById("fb-payload").textContent.trim();

        new QRCode(document.getElementById("fb-qrcode"), {
            text: fbPayload,
            width: 230,
            height: 230,
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

        /* ---- Copiar código PIX (copia e cola) ---- */

        const boxPayload = document.getElementById("fb-payload");
        const btnCopiar = document.getElementById("fb-copiar");

        async function copiarPix() {
            try {
                await navigator.clipboard.writeText(fbPayload);
            } catch (e) {
                prompt("Copie o código PIX abaixo:", fbPayload);
                return;
            }
            boxPayload.classList.add("copiado");
            setTimeout(() => boxPayload.classList.remove("copiado"), 2200);
            if (btnCopiar) {
                const original = btnCopiar.textContent;
                btnCopiar.textContent = "Copiado!";
                btnCopiar.disabled = true;
                setTimeout(() => {
                    btnCopiar.textContent = original;
                    btnCopiar.disabled = false;
                }, 2200);
            }
        }

        btnCopiar?.addEventListener("click", copiarPix);
        boxPayload?.addEventListener("click", copiarPix);
        boxPayload?.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                copiarPix();
            }
        });

        /* ---- Copiar só a chave PIX ---- */

        const chaveValor = document.getElementById("fb-chave-valor");
        const campoChave = chaveValor?.closest(".fb-doc-chave");

        async function copiarChave() {
            const chave = chaveValor.textContent.trim();
            try {
                await navigator.clipboard.writeText(chave);
            } catch (e) {
                prompt("Copie a chave PIX abaixo:", chave);
                return;
            }
            campoChave?.classList.add("copiado");
            setTimeout(() => campoChave?.classList.remove("copiado"), 2200);
        }

        chaveValor?.addEventListener("click", copiarChave);
        chaveValor?.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                copiarChave();
            }
        });

        /* ---- Enviar por WhatsApp (somente dono) ---- */

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

        /* ---- Marcar como pago (somente dono) ---- */

        const btnPago = document.getElementById("fb-marcar-pago");
        btnPago?.addEventListener("click", async () => {
            btnPago.disabled = true;
            const original = btnPago.textContent;
            btnPago.textContent = "Salvando...";
            try {
                const fd = new FormData();
                fd.append("acao", "atualizar_status");
                fd.append("cobranca_id", <?= json_encode((string)$cob['id']) ?>);
                fd.append("status", "pago");
                const r = await fetch("../funcoes/frutibank/api.php", { method: "POST", body: fd, credentials: "same-origin" });
                const d = await r.json();
                if (!d.ok) throw new Error(d.msg || "Erro ao atualizar");
                btnPago.textContent = "✓ Pago";
            } catch (e) {
                btnPago.textContent = original;
                btnPago.disabled = false;
                alert("Não foi possível marcar como pago: " + e.message);
            }
        });
    </script>
</body>
</html>
