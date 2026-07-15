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

        <main class="sistema fundo-img au-page">
            <div class="container au-shell">
                <header class="au-header">
                    <h1>Painel administrativo</h1>
                    <p>Gerencie os usuários do Caderno: crie usuários locais, defina perfis, ative ou desative contas e acesse o caderno de qualquer usuário.</p>
                </header>

                <section class="au-card">
                    <div class="au-card-head au-accent-verde">
                        <div>
                            <h2>Criar usuário local</h2>
                            <p>Usuário com login e senha próprios, sem depender do cadastro Frutag.</p>
                        </div>
                    </div>

                    <form class="au-form" id="form-criar-usuario">
                        <div class="au-field au-field-wide">
                            <label for="cu-nome">Nome completo</label>
                            <input type="text" id="cu-nome" name="nome" placeholder="Ex.: Maria da Silva" required>
                        </div>
                        <div class="au-field">
                            <label for="cu-login">Login <small>(mín. 3 caracteres)</small></label>
                            <input type="text" id="cu-login" name="login" placeholder="Ex.: maria.silva" required autocomplete="off">
                        </div>
                        <div class="au-field">
                            <label for="cu-email">E-mail <small>(opcional)</small></label>
                            <input type="email" id="cu-email" name="email" placeholder="Ex.: maria@email.com">
                        </div>
                        <div class="au-field">
                            <label for="cu-senha">Senha <small>(mín. 8 caracteres)</small></label>
                            <input type="password" id="cu-senha" name="senha" placeholder="••••••••" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="au-field">
                            <label for="cu-perfil">Perfil</label>
                            <select id="cu-perfil" name="perfil">
                                <option value="usuario" selected>Usuário</option>
                                <option value="representante">Representante</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="au-form-actions">
                            <button type="submit" class="main-btn fundo-verde">Criar usuário</button>
                        </div>
                    </form>
                </section>

                <section class="au-card">
                    <div class="au-card-head">
                        <div>
                            <h2>Usuários</h2>
                            <p>Todos os usuários do Caderno (locais e Frutag). Alterações de perfil valem imediatamente.</p>
                        </div>
                        <span class="au-chip" id="au-total">—</span>
                    </div>

                    <form class="au-search" id="form-busca-usuario">
                        <input type="search" name="q" placeholder="Buscar por nome, login, e-mail ou ID..." autocomplete="off">
                        <button type="submit">Buscar</button>
                    </form>

                    <div class="au-table-wrap">
                        <table class="au-table" id="tabela-usuarios">
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
