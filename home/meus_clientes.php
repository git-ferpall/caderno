<?php
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

require_perfil(['representante', 'admin']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Clientes — Caderno Frutag</title>
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
                    <h1>Meus clientes</h1>
                    <p>Cadastre clientes com login e senha próprios e acompanhe o caderno de campo de cada um. Você só enxerga os clientes cadastrados por você.</p>
                </header>

                <section class="admin-offline-card">
                    <h2>Cadastrar cliente</h2>
                    <p class="admin-offline-hint">O cliente receberá um usuário local do Caderno e poderá entrar com o login e a senha definidos aqui.</p>

                    <form class="admin-offline-form admin-usuarios-form" id="form-criar-cliente">
                        <input type="text" name="nome" placeholder="Nome completo" required>
                        <input type="text" name="login" placeholder="Login (mín. 3 caracteres)" required autocomplete="off">
                        <input type="email" name="email" placeholder="E-mail (opcional)">
                        <input type="password" name="senha" placeholder="Senha (mín. 8 caracteres)" required minlength="8" autocomplete="new-password">
                        <button type="submit" class="main-btn fundo-verde">Cadastrar cliente</button>
                    </form>
                </section>

                <section class="admin-offline-card">
                    <h2>Clientes cadastrados</h2>

                    <form class="admin-offline-search" id="form-busca-cliente">
                        <input type="search" name="q" placeholder="Buscar por nome, login ou e-mail..." autocomplete="off">
                        <button type="submit" class="main-btn fundo-azul">Buscar</button>
                    </form>

                    <div class="admin-offline-table-wrap">
                        <table class="admin-offline-table" id="tabela-clientes">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Login / E-mail</th>
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
        <script src="../js/meus_clientes.js"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
