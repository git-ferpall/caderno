<?php
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../configuracao/usuarios_local.php'; // conexão ($mysqli) + helpers

// dono da conta ou funcionário com papel 'admin'
require_conta_gestao();

// Liberação aprovada pelo administrativo (define também o limite de acessos)
$contaId    = (int)($GLOBALS['auth_user']->sub ?? 0);
$funcConfig = contaFuncConfig($mysqli, $contaId);
$funcLimite = $funcConfig !== null ? (int)$funcConfig['limite'] : 0;
$funcAtivos = contaFuncContarAtivos($mysqli, $contaId);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários da Conta — Caderno Frutag</title>
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
                    <h1>Usuários da conta</h1>
                    <p>Crie acessos para funcionários no seu caderno: <strong>Administrador</strong> gerencia tudo (inclusive outros usuários) e <strong>Apontador</strong> apenas registra apontamentos.</p>
                </header>

                <?php if ($funcConfig === null): ?>
                <section class="au-card">
                    <div class="au-card-head">
                        <div>
                            <h2>Função não liberada</h2>
                            <p>A criação de acessos para funcionários precisa ser aprovada pelo administrativo. Entre em contato para solicitar a liberação e a quantidade de acessos.</p>
                        </div>
                    </div>
                </section>
                <?php else: ?>
                <section class="au-card">
                    <div class="au-card-head au-accent-verde">
                        <div>
                            <h2>Criar acesso</h2>
                            <p>O funcionário entra com login e senha próprios e trabalha nos dados desta conta. Sua conta pode ter até <strong><?= $funcLimite ?></strong> acesso(s) ativo(s).</p>
                        </div>
                        <span class="au-chip"><?= $funcAtivos ?> de <?= $funcLimite ?> em uso</span>
                    </div>

                    <form class="au-form" id="form-criar-funcionario">
                        <div class="au-field au-field-wide">
                            <label for="cf-nome">Nome completo</label>
                            <input type="text" id="cf-nome" name="nome" placeholder="Ex.: João da Silva" required>
                        </div>
                        <div class="au-field">
                            <label for="cf-login">Login <small>(mín. 3 caracteres)</small></label>
                            <input type="text" id="cf-login" name="login" placeholder="Ex.: joao.silva" required autocomplete="off">
                        </div>
                        <div class="au-field">
                            <label for="cf-email">E-mail <small>(opcional)</small></label>
                            <input type="email" id="cf-email" name="email" placeholder="Ex.: joao@email.com">
                        </div>
                        <div class="au-field">
                            <label for="cf-senha">Senha <small>(mín. 8 caracteres)</small></label>
                            <input type="password" id="cf-senha" name="senha" placeholder="••••••••" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="au-field">
                            <label for="cf-papel">Papel na conta</label>
                            <select id="cf-papel" name="papel_conta">
                                <option value="apontador" selected>Apontador (só apontamentos)</option>
                                <option value="admin">Administrador da conta</option>
                            </select>
                        </div>
                        <div class="au-form-actions">
                            <button type="submit" class="main-btn fundo-verde">Criar acesso</button>
                        </div>
                    </form>
                </section>
                <?php endif; ?>

                <section class="au-card">
                    <div class="au-card-head">
                        <div>
                            <h2>Acessos da conta</h2>
                            <p>Alterações de papel e desativações valem imediatamente.</p>
                        </div>
                        <span class="au-chip" id="cf-total">—</span>
                    </div>

                    <div class="au-table-wrap">
                        <table class="au-table" id="tabela-funcionarios">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Login / E-mail</th>
                                    <th>Papel</th>
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
        <script src="../js/usuarios_conta.js"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
