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

        <main class="sistema fundo-img au-page">
            <div class="container au-shell">
                <header class="au-header">
                    <h1>Meus clientes</h1>
                    <p>Cadastre clientes com login e senha próprios e acompanhe o caderno de campo de cada um. Você só enxerga os clientes cadastrados por você.</p>
                </header>

                <section class="au-card">
                    <div class="au-card-head au-accent-verde">
                        <div>
                            <h2>Cadastrar cliente</h2>
                            <p>O cliente receberá um usuário local do Caderno e poderá entrar com o login e a senha definidos aqui.</p>
                        </div>
                    </div>

                    <form class="au-form" id="form-criar-cliente">
                        <div class="au-field au-field-wide">
                            <label for="cc-nome">Nome completo</label>
                            <input type="text" id="cc-nome" name="nome" placeholder="Ex.: João Pereira" required>
                        </div>
                        <div class="au-field">
                            <label for="cc-login">Login <small>(mín. 3 caracteres)</small></label>
                            <input type="text" id="cc-login" name="login" placeholder="Ex.: joao.pereira" required autocomplete="off">
                        </div>
                        <div class="au-field">
                            <label for="cc-email">E-mail <small>(opcional)</small></label>
                            <input type="email" id="cc-email" name="email" placeholder="Ex.: joao@email.com">
                        </div>
                        <div class="au-field">
                            <label for="cc-senha">Senha <small>(mín. 8 caracteres)</small></label>
                            <input type="password" id="cc-senha" name="senha" placeholder="••••••••" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="au-form-actions">
                            <button type="submit" class="main-btn fundo-verde">Cadastrar cliente</button>
                        </div>
                    </form>
                </section>

                <section class="au-card">
                    <div class="au-card-head">
                        <div>
                            <h2>Clientes cadastrados</h2>
                            <p>Acompanhe, redefina senhas e acesse o caderno de cada cliente.</p>
                        </div>
                        <span class="au-chip" id="au-total">—</span>
                    </div>

                    <form class="au-search" id="form-busca-cliente">
                        <input type="search" name="q" placeholder="Buscar por nome, login ou e-mail..." autocomplete="off">
                        <button type="submit">Buscar</button>
                    </form>

                    <div class="au-table-wrap">
                        <table class="au-table" id="tabela-clientes">
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
