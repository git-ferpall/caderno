<?php
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../funcoes/offline/helpers.php';

$user = $GLOBALS['auth_user'] ?? null;
$user_id = (int)($user->sub ?? 0);

if (!$user_id || !offlineIsAdmin($mysqli, $user_id)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Acesso negado</title></head><body style="font-family:sans-serif;padding:40px;text-align:center"><h1>Acesso negado</h1><p>Esta área é restrita a administradores Frutag autorizados.</p><p><a href="/home">Voltar</a></p></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Offline — Caderno Frutag</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body>
    <?php require '../include/loading.php' ?>
    <?php include '../include/popups.php' ?>

    <div id="conteudo">
        <?php include '../include/menu.php' ?>

        <main class="sistema fundo-img admin-offline-page">
            <div class="container admin-offline-shell">
                <header class="admin-offline-header">
                    <h1>Modo offline</h1>
                    <p>Controle quais clientes podem registrar apontamentos sem internet. Relatórios continuam exigindo conexão.</p>
                </header>

                <section class="admin-offline-card">
                    <h2>Administradores Frutag</h2>
                    <p class="admin-offline-hint">Somente estes usuários acessam este painel.</p>

                    <form class="admin-offline-form" id="form-add-admin">
                        <input type="number" name="user_id" placeholder="ID usuário Frutag" required min="1">
                        <input type="text" name="nome" placeholder="Nome (opcional)">
                        <input type="email" name="email" placeholder="E-mail (opcional)">
                        <button type="submit" class="main-btn fundo-azul">Adicionar admin</button>
                    </form>

                    <div class="admin-offline-table-wrap">
                        <table class="admin-offline-table" id="tabela-admins">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Desde</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-offline-card">
                    <h2>Clientes com offline</h2>
                    <p class="admin-offline-hint">Habilite o modo offline por cliente (user_id do Caderno).</p>

                    <form class="admin-offline-search" id="form-busca-usuario">
                        <input type="search" name="q" placeholder="Buscar por nome, e-mail ou ID..." autocomplete="off">
                        <button type="submit" class="main-btn fundo-azul">Buscar</button>
                    </form>

                    <div class="admin-offline-table-wrap">
                        <table class="admin-offline-table" id="tabela-usuarios">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente / propriedade</th>
                                    <th>E-mail</th>
                                    <th>Offline</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/admin_offline.js"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
