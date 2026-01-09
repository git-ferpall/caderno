<?php
/**
 * Validação de integridade do checklist
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../funcoes/gerar_hash.php';

/* 📥 Recebe hash */
$hash = $_GET['hash'] ?? '';

if (!$hash || strlen($hash) !== 64) {
    die('Hash inválido');
}

/* 🔎 Busca checklist pelo hash */
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

/* 🔍 Validação */
$integro = hash_equals($checklist['hash_documento'], $hash_atual);
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Validação de Checklist</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

<h3>🔎 Validação de Checklist</h3>

<p><strong>Título:</strong> <?= htmlspecialchars($checklist['titulo']) ?></p>
<p><strong>Fechado em:</strong> <?= htmlspecialchars($checklist['fechado_em']) ?></p>
<p><strong>Hash:</strong><br><code><?= htmlspecialchars($hash) ?></code></p>

<?php if ($integro): ?>
<div class="alert alert-success">
    ✅ Checklist íntegro<br>
    O documento não sofreu alterações após o fechamento.
</div>
<?php else: ?>
<div class="alert alert-danger">
    ❌ Checklist adulterado<br>
    O conteúdo foi alterado após o fechamento.
</div>
<?php endif; ?>

</div>

</body>
</html>
