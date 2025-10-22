<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/../funcoes/busca_dados.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

$apontamentos = getApontamentosCompletos($mysqli, $user_id);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dados - Apontamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        table { font-size: 0.9rem; }
        .detalhes {
            font-size: 0.85rem;
            color: #555;
        }
    </style>
</head>
<body>
<div class="container">
    <h3 class="mb-4">📋 Apontamentos da Propriedade Ativa</h3>

    <?php if (empty($apontamentos)) : ?>
        <div class="alert alert-warning">Nenhum apontamento encontrado.</div>
    <?php else : ?>
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-success">
                <tr>
                    <th>#</th>
                    <th>Tipo</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Áreas</th>
                    <th>Produtos</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($apontamentos as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['id']) ?></td>
                        <td><?= htmlspecialchars($a['tipo']) ?></td>
                        <td><?= date('d/m/Y', strtotime($a['data'])) ?></td>
                        <td>
                            <span class="badge bg-<?= 
                                $a['status'] == 'concluido' ? 'success' : 
                                ($a['status'] == 'pendente' ? 'warning' : 'secondary') ?>">
                                <?= ucfirst($a['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php foreach ($a['areas'] as $ar) echo "<div>• " . htmlspecialchars($ar) . "</div>"; ?>
                        </td>
                        <td>
                            <?php foreach ($a['produtos'] as $p) echo "<div>• " . htmlspecialchars($p) . "</div>"; ?>
                        </td>
                        <td class="detalhes">
                            <?php foreach ($a['detalhes'] as $k => $v): ?>
                                <div><strong><?= htmlspecialchars($k) ?>:</strong> <?= htmlspecialchars($v) ?></div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
