<?php
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

require_admin();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo — Caderno Frutag</title>
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
                    <h1>Painel administrativo</h1>
                    <p>Gerencie os usuários do Caderno: crie usuários locais, defina perfis (usuário, representante ou administrador), ative/desative contas e acesse o caderno de qualquer usuário.</p>
                </header>

                <section class="admin-offline-card">
                    <h2>Criar usuário local</h2>
                    <p class="admin-offline-hint">Usuário com login e senha próprios, sem depender do cadastro Frutag.</p>

                    <form class="admin-offline-form admin-usuarios-form" id="form-criar-usuario">
                        <input type="text" name="nome" placeholder="Nome completo" required>
                        <input type="text" name="login" placeholder="Login (mín. 3 caracteres)" required autocomplete="off">
                        <input type="email" name="email" placeholder="E-mail (opcional)">
                        <input type="password" name="senha" placeholder="Senha (mín. 8 caracteres)" required minlength="8" autocomplete="new-password">
                        <select name="perfil" aria-label="Perfil do novo usuário">
                            <option value="usuario" selected>Usuário</option>
                            <option value="representante">Representante</option>
                            <option value="admin">Administrador</option>
                        </select>
                        <button type="submit" class="main-btn fundo-verde">Criar usuário</button>
                    </form>
                </section>

                <section class="admin-offline-card">
                    <h2>Usuários</h2>
                    <p class="admin-offline-hint">Todos os usuários do Caderno (locais e Frutag). Alterações de perfil valem imediatamente.</p>

                    <form class="admin-offline-search" id="form-busca-usuario">
                        <input type="search" name="q" placeholder="Buscar por nome, login, e-mail ou ID..." autocomplete="off">
                        <button type="submit" class="main-btn fundo-azul">Buscar</button>
                    </form>

                    <div class="admin-offline-table-wrap">
                        <table class="admin-offline-table" id="tabela-usuarios">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Login / E-mail</th>
                                    <th>Origem</th>
                                    <th>Perfil</th>
                                    <th>Ativo</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/admin_usuarios.js"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
