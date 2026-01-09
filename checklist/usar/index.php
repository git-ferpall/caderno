<?php
/**
 * Página inicial do módulo Checklist
 * Lista modelos disponíveis para o usuário
 *
 * Padrão de autenticação:
 * 1) Sessão (quando existir)
 * 2) JWT via SSO (verify_jwt)
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

/* 🚫 Bloqueio definitivo se não autenticado */
if (!$user_id) {
    http_response_code(401);
    die('Usuário não autenticado');
}

/* 🔎 Busca modelos disponíveis */
$sql = "
    SELECT
        id,
        titulo,
        descricao,
        criado_por,
        publico,
        criado_em
    FROM checklist_modelos
    WHERE publico = 1 OR criado_por = ?
    ORDER BY titulo
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Checklists</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

    <h3 class="mb-4">📋 Checklists disponíveis</h3>

    <?php if (empty($modelos)): ?>
        <div class="alert alert-info">
            Nenhum checklist disponível para você.
        </div>
    <?php endif; ?>

    <div class="row">
    <?php foreach ($modelos as $m): ?>
        <div class="col-md-4">
            <div class="card mb-3 h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">
                        <?= htmlspecialchars($m['titulo']) ?>
                    </h5>

                    <?php if (!empty($m['descricao'])): ?>
                        <p class="card-text text-muted">
                            <?= nl2br(htmlspecialchars($m['descricao'])) ?>
                        </p>
                    <?php endif; ?>

                    <div class="mt-auto">
                        <form method="post" action="criar.php">
                            <input type="hidden" name="modelo_id" value="<?= $m['id'] ?>">
                            <button class="btn btn-primary w-100">
                                Usar este checklist
                            </button>
                        </form>

                        <?php if ($m['criado_por'] == $user_id): ?>
                            <small class="text-muted d-block mt-2">
                                Modelo pessoal
                            </small>
                        <?php else: ?>
                            <small class="text-muted d-block mt-2">
                                Modelo padrão do sistema
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

</div>

</body>
</html>
