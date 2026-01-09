<?php

/**
 * Página inicial do módulo Checklist
 * (MySQLi + SSO + Sessão)
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

session_start();

/* 🔐 Recupera user_id (sessão → JWT) */
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    http_response_code(401);
    die('Usuário não autenticado');
}

$sql = "
SELECT *
FROM checklist_modelos
WHERE publico = 1 OR criado_por = ?
ORDER BY criado_em DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h3>Modelos de Checklist</h3>

<a href="criar.php" class="btn btn-success mb-3">+ Novo modelo</a>

<table class="table table-striped">
<thead>
<tr>
    <th>Título</th>
    <th>Tipo</th>
    <th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach ($modelos as $m): ?>
<tr>
    <td><?= htmlspecialchars($m['titulo']) ?></td>
    <td><?= $m['criado_por'] ? 'Pessoal' : 'Padrão' ?></td>
    <td>
        <a href="editar.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
        <a href="excluir.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-danger"
           onclick="return confirm('Excluir este modelo?')">Excluir</a>
    </td>
</tr>
<?php endforeach ?>
</tbody>
</table>
