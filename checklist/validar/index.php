<?php
/**
 * Validação pública de integridade de checklist
 * (sem exibição de dados pessoais – LGPD)
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../funcoes/gerar_hash.php';

/* 📥 Hash */
$hash = $_GET['hash'] ?? '';
if (!$hash || strlen($hash) !== 64) {
    die('Hash inválido');
}

/* 🔎 Busca checklist */
$stmt = $mysqli->prepare("
    SELECT id, titulo, fechado_em, hash_documento
    FROM checklists
    WHERE hash_documento = ?
    LIMIT 1
");
$stmt->bind_param("s", $hash);
$stmt->execute();
$checklist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$checklist) {
    die('Documento não encontrado');
}

/* 🔐 Recalcula hash */
$hash_atual = gerarHashChecklist($mysqli, (int)$checklist['id']);
$integro = hash_equals($checklist['hash_documento'], $hash_atual);

/* 📄 PDF */
$pdfUrl = "/checklist/pdf/gerar.php?id=" . $checklist['id'];


/* 🧾 URL curta */
$urlCurta = "/v/" . $hash;

/* 🖼 Logo */
$logo = "/../../img/logo-color.png";
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Validação de Documento</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(135deg, #f4f6f8, #eef2f5);
}

.logo {
    max-height: 180px;
}

.card-validacao {
    border-radius: 14px;
    border: none;
    box-shadow: 0 12px 30px rgba(0,0,0,.08);
}

.selo {
    display: inline-block;
    padding: 8px 18px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
}

.selo-ok {
    background: #e8f5e9;
    color: #2e7d32;
    border: 2px solid #2e7d32;
}

.selo-no {
    background: #fdecea;
    color: #c62828;
    border: 2px solid #c62828;
}

.hash-box {
    font-size: .85rem;
    background: #f8f9fa;
    border: 1px dashed #ced4da;
    border-radius: 6px;
    padding: 10px;
    word-break: break-all;
}

.termo {
    font-size: .8rem;
    color: #666;
}

.footer {
    font-size: .8rem;
    color: #777;
    text-align: center;
    margin-top: 25px;
}
</style>
</head>

<body>

<div class="container py-5">
<div class="row justify-content-center">
<div class="col-lg-8">

<!-- LOGO -->
<div class="text-center mb-4">
    <img src="<?= $logo ?>" class="logo" alt="Frutag">
</div>

<div class="card card-validacao p-4">

<div class="text-center mb-3">
    <h3>Validação de Documento</h3>

    <?php if ($integro): ?>
        <div class="selo selo-ok mt-2">✔ DOCUMENTO ÍNTEGRO</div>
    <?php else: ?>
        <div class="selo selo-no mt-2">✖ DOCUMENTO ADULTERADO</div>
    <?php endif; ?>
</div>

<hr>

<p><strong>Título:</strong><br><?= htmlspecialchars($checklist['titulo']) ?></p>
<p><strong>Data de fechamento:</strong><br><?= htmlspecialchars($checklist['fechado_em']) ?></p>

<p class="mb-2"><strong>Hash criptográfico:</strong></p>
<div class="hash-box mb-3"><?= htmlspecialchars($hash) ?></div>


<hr>

<!-- MINIATURA PDF -->
<h6>📄 Documento original</h6>

<div class="ratio ratio-16x9 mb-2">
    <iframe src="<?= $pdfUrl ?>#page=1&zoom=75"></iframe>
</div>

<a href="<?= $pdfUrl ?>" class="btn btn-outline-primary btn-sm" target="_blank">
⬇ Baixar PDF
</a>

<hr>

<div class="termo">
<strong>Termo legal / LGPD</strong><br>
Esta página valida exclusivamente a integridade criptográfica do documento,
não exibindo dados pessoais ou sensíveis. A verificação é realizada por meio
de hash criptográfico, conforme boas práticas de segurança da informação e
em conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018).
</div>

</div>

<div class="footer">
Sistema Caderno de Campo · Frutag<br>
Validação pública de documentos
</div>

</div>
</div>
</div>

</body>
</html>
