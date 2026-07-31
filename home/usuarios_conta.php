<?php
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../configuracao/usuarios_local.php';
require_once __DIR__ . '/../funcoes/conta/helpers.php';

require_conta_gestao();

$contaId       = (int)($GLOBALS['auth_user']->sub ?? 0);
$funcConfig    = contaFuncConfig($mysqli, $contaId);
$funcLimite    = $funcConfig !== null ? (int)$funcConfig['limite'] : 0;
$funcAtivos    = contaFuncContarAtivos($mysqli, $contaId);
$totalProps    = count(contaListarPropriedadesIds($mysqli, $contaId));
$vagasRestantes = max(0, $funcLimite - $funcAtivos);
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

        <main class="sistema fundo-img uc-page">
            <div class="container uc-shell">

                <header class="uc-hero">
                    <div class="uc-hero-text">
                        <p class="uc-eyebrow">Gestão de equipe</p>
                        <h1>Usuários da conta</h1>
                        <p>Crie acessos para sua equipe trabalhar no mesmo caderno, com permissões e propriedades definidas por você.</p>
                    </div>
                    <?php if ($funcConfig !== null): ?>
                    <div class="uc-stats">
                        <div class="uc-stat uc-stat--verde">
                            <strong><?= $funcAtivos ?>/<?= $funcLimite ?></strong>
                            <span>Acessos ativos</span>
                        </div>
                        <div class="uc-stat">
                            <strong><?= $vagasRestantes ?></strong>
                            <span>Vagas disponíveis</span>
                        </div>
                        <div class="uc-stat uc-stat--azul">
                            <strong><?= $totalProps ?></strong>
                            <span>Propriedades</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </header>

                <div class="uc-roles">
                    <div class="uc-role-card">
                        <div class="uc-role-icon uc-role-icon--admin" aria-hidden="true">A</div>
                        <div>
                            <h3>Administrador da conta</h3>
                            <p>Gerencia cadastros, relatórios, propriedades e outros usuários da conta.</p>
                        </div>
                    </div>
                    <div class="uc-role-card">
                        <div class="uc-role-icon uc-role-icon--apont" aria-hidden="true">P</div>
                        <div>
                            <h3>Apontador</h3>
                            <p>Registra apontamentos de campo nas propriedades liberadas, sem acesso à gestão.</p>
                        </div>
                    </div>
                </div>

                <?php if ($funcConfig === null): ?>
                <section class="uc-card">
                    <div class="uc-locked">
                        <div class="uc-locked-icon" aria-hidden="true">🔒</div>
                        <h2>Função não liberada</h2>
                        <p>A criação de acessos para funcionários precisa ser aprovada pelo administrativo. Entre em contato para solicitar a liberação e a quantidade de acessos.</p>
                    </div>
                </section>
                <?php else: ?>
                <section class="uc-card">
                    <div class="uc-card-head uc-card-head--verde">
                        <div>
                            <h2>Novo acesso</h2>
                            <p>O funcionário entra com login e senha próprios. Limite da conta: <strong><?= $funcLimite ?></strong> acesso(s) ativo(s).</p>
                        </div>
                        <span class="uc-chip uc-chip--verde"><?= $funcAtivos ?> em uso</span>
                    </div>
                    <div class="uc-card-body">
                        <form id="form-criar-funcionario" class="uc-form-grid" novalidate>
                            <div class="uc-form-section">
                                <p class="uc-section-title">Identificação</p>
                                <div class="uc-field">
                                    <label for="cf-nome">Nome completo</label>
                                    <input type="text" id="cf-nome" name="nome" placeholder="Ex.: João da Silva" required autocomplete="name">
                                </div>
                                <div class="uc-field">
                                    <label for="cf-login">Login <small>(mín. 3 caracteres)</small></label>
                                    <input type="text" id="cf-login" name="login" placeholder="Ex.: joao.silva" required autocomplete="off" autocapitalize="off">
                                </div>
                                <div class="uc-field">
                                    <label for="cf-email">E-mail <small>(opcional)</small></label>
                                    <input type="email" id="cf-email" name="email" placeholder="Ex.: joao@email.com" autocomplete="email">
                                </div>
                                <div class="uc-field">
                                    <label for="cf-senha">Senha <small>(mín. 8 caracteres)</small></label>
                                    <input type="password" id="cf-senha" name="senha" placeholder="••••••••" required minlength="8" autocomplete="new-password">
                                </div>
                            </div>

                            <div class="uc-form-section">
                                <p class="uc-section-title">Permissões</p>
                                <div class="uc-field">
                                    <label for="cf-papel">Papel na conta</label>
                                    <select id="cf-papel" name="papel_conta">
                                        <option value="apontador" selected>Apontador — só apontamentos</option>
                                        <option value="admin">Administrador — gestão completa</option>
                                    </select>
                                </div>
                                <div class="uc-field">
                                    <label>Propriedades com acesso</label>
                                    <div class="uc-props-toolbar">
                                        <p>Marque onde este usuário poderá trabalhar.</p>
                                        <div class="uc-props-actions">
                                            <button type="button" class="uc-link-btn" data-props-all>Marcar todas</button>
                                            <button type="button" class="uc-link-btn" data-props-none>Desmarcar</button>
                                        </div>
                                    </div>
                                    <div id="cf-propriedades" class="uc-props-lista"></div>
                                </div>
                            </div>

                            <div class="uc-form-actions">
                                <button type="submit" class="main-btn fundo-verde">Criar acesso</button>
                            </div>
                        </form>
                    </div>
                </section>
                <?php endif; ?>

                <section class="uc-card">
                    <div class="uc-card-head">
                        <div>
                            <h2>Equipe cadastrada</h2>
                            <p>Alterações de papel, propriedades e status entram em vigor imediatamente.</p>
                        </div>
                        <span class="uc-chip" id="cf-total">—</span>
                    </div>
                    <div class="uc-card-body" style="padding-top:8px">
                        <div class="uc-table-wrap">
                            <table class="uc-table" id="tabela-funcionarios">
                                <thead>
                                    <tr>
                                        <th>Membro</th>
                                        <th>Credenciais</th>
                                        <th>Papel</th>
                                        <th>Propriedades</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <div id="modal-propriedades" class="uc-modal d-none" role="dialog" aria-modal="true" aria-labelledby="modal-prop-titulo">
            <div class="uc-modal-panel">
                <div class="uc-modal-head">
                    <div>
                        <h3 id="modal-prop-titulo">Propriedades do acesso</h3>
                        <p id="modal-prop-subtitulo"></p>
                    </div>
                    <button type="button" class="uc-modal-close" id="modal-prop-fechar" aria-label="Fechar">&times;</button>
                </div>
                <div class="uc-modal-body">
                    <div class="uc-props-toolbar">
                        <p>Selecione as propriedades liberadas.</p>
                        <div class="uc-props-actions">
                            <button type="button" class="uc-link-btn" data-modal-props-all>Marcar todas</button>
                            <button type="button" class="uc-link-btn" data-modal-props-none>Desmarcar</button>
                        </div>
                    </div>
                    <div id="modal-prop-lista" class="uc-props-lista"></div>
                </div>
                <div class="uc-modal-foot">
                    <button type="button" class="uc-btn-ghost" id="modal-prop-cancelar">Cancelar</button>
                    <button type="button" class="main-btn fundo-verde" id="modal-prop-salvar">Salvar</button>
                </div>
            </div>
        </div>

        <?php include '../include/imports.php' ?>
        <script src="../js/usuarios_conta.js?v=5"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
