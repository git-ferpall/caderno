<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Garante login */
$user = require_login();
$user_id = (int) $user->sub;

/* 🔒 BASE DO SISTEMA */
define('APP_PATH', realpath(__DIR__ . '/../../'));

/* 🔎 Buscar modelos */
$sql = "
    SELECT
        id,
        titulo,
        criado_por,
        publico,
        criado_em
    FROM checklist_modelos
    WHERE ativo = 1
      AND (publico = 1 OR criado_por = ?)
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="/">

    <title>Modelos de Checklist</title>

    <link rel="icon" type="image/png" href="/img/logo-icon.png">
    <link rel="stylesheet" href="/css/style.css">

    <style>
        .page-content {
            margin-top: 80px;
        }
        .table-rounded {
            border-radius: 10px;
            overflow: hidden;
            background-color: #fff;
        }
        main.sistema {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 18px;
            padding: 28px;

            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(4px);
        }
    </style>
</head>

<body class="bg-light">

<?php require APP_PATH . '/include/loading.php'; ?>
<?php require APP_PATH . '/include/popups.php'; ?>

<div id="conteudo">

    <?php require APP_PATH . '/include/menu.php'; ?>

    <main class="sistema">
        <div class="container py-4 page-content">

            <h2 class="mb-3">📋 Modelos de Checklist</h2>

            <div class="text-start">
                <a href="/checklist/modelos/criar.php" class="btn btn-success mb-3" style="background-color:#E95D24; color:#fff;">
                    ➕ Novo modelo
                </a>
            </div>


            <?php if (empty($modelos)): ?>
                <div class="alert alert-warning">
                    Nenhum modelo cadastrado.
                </div>
            <?php else: ?>

                <table class="table table-striped table-rounded">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Criado em</th>
                            <th style="width:180px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modelos as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['titulo']) ?></td>
                            <td><?= $m['criado_por'] ? 'Pessoal' : 'Padrão' ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($m['criado_em'])) ?></td>
                            <td>
                                <a href="/checklist/modelos/criar.php?id=<?= $m['id'] ?>"
                                   class="btn btn-sm btn-primary">
                                    Editar
                                </a>

                                <a href="/checklist/modelos/desabilitar.php?id=<?= $m['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Excluir este modelo?')">
                                    Excluir
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>
    </main>

    <?php require APP_PATH . '/include/footer.php'; ?>

</div>

<!-- JS -->
<script src="/js/jquery.js"></script>
<script src="/js/main.js"></script>
<script src="/js/popups.js"></script>
<script src="/js/script.js"></script>

</body>
</html>
