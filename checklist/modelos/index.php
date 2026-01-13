<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';
/* 🔒 BASE DO SISTEMA */
define('APP_PATH', realpath(__DIR__ . '/../../'));
/*
 * 🔒 Garante login:
 * - se não estiver logado → redirect
 * - se estiver logado → retorna JWT (claims)
 */
$user = require_login();

/* 👤 ID do usuário autenticado */
$user_id = (int) $user->sub;

/* 🔎 Buscar modelos */
$sql = "
    SELECT
        id,
        titulo,
        criado_por,
        publico,
        criado_em
    FROM checklist_modelos
    WHERE publico = 1 OR criado_por = ?
    ORDER BY criado_em DESC
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$modelos = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Modelos de Checklist</title>
    <base href="/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
    <link rel="stylesheet" href="/css/style.css">
    <div class="container-fluid">
        
    </div>
    <style>
        body {
            padding-top: 80px; /* mesma altura do menu */
        }
    </style>    
</head>

<body class="bg-light">
    <?php require APP_PATH . '/include/menu.php'; ?>
    <div class="container py-4">
        
        
        
        <h2 class="mb-3">📋 Modelos de Checklist</h2>

        <a href="criar.php" class="btn btn-success mb-3">
            ➕ Novo modelo
        </a>

        <?php if (empty($modelos)): ?>
            <div class="alert alert-warning">
                Nenhum modelo cadastrado.
            </div>
        <?php else: ?>

        <table class="table table-striped">
        <thead>
        <tr>
            <th>Título</th>
            <th>Tipo</th>
            <th>Criado em</th>
            <th width="180">Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($modelos as $m): ?>
        <tr>
            <td><?= htmlspecialchars($m['titulo']) ?></td>
            <td><?= $m['criado_por'] ? 'Pessoal' : 'Padrão' ?></td>
            <td><?= date('d/m/Y H:i', strtotime($m['criado_em'])) ?></td>
            <td>
                <a href="criar.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-primary">
                    Editar
                </a>

                <a href="excluir.php?id=<?= $m['id'] ?>"
                class="btn btn-sm btn-danger"
                onclick="return confirm('Excluir este modelo?')">
                    Excluir
                </a>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
        </table>

        <?php endif; ?>

    </div>

</body>
</html>
