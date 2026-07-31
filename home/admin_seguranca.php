<?php
require_once __DIR__ . '/../configuracao/protect.php';

require_admin();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segurança — Caderno Frutag</title>
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
                    <h1>Segurança e auditoria</h1>
                    <p>Monitore tentativas de login, falhas de autenticação e possíveis ataques de força bruta.</p>
                </header>

                <section class="au-card">
                    <div class="au-card-head au-accent-verde">
                        <div>
                            <h2>Resumo</h2>
                            <p id="as-resumo-periodo">Últimos 7 dias</p>
                        </div>
                    </div>
                    <div class="as-stats" id="as-stats">
                        <div class="as-stat">
                            <span class="as-stat-value" id="as-total">—</span>
                            <span class="as-stat-label">Total</span>
                        </div>
                        <div class="as-stat as-stat-ok">
                            <span class="as-stat-value" id="as-sucessos">—</span>
                            <span class="as-stat-label">Sucessos</span>
                        </div>
                        <div class="as-stat as-stat-fail">
                            <span class="as-stat-value" id="as-falhas">—</span>
                            <span class="as-stat-label">Falhas</span>
                        </div>
                    </div>
                </section>

                <div class="as-grid-2">
                    <section class="au-card">
                        <div class="au-card-head">
                            <h2>Falhas por motivo</h2>
                        </div>
                        <div class="au-table-wrap">
                            <table class="au-table" id="tabela-motivos">
                                <thead>
                                    <tr>
                                        <th>Motivo</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </section>

                    <section class="au-card">
                        <div class="au-card-head">
                            <h2>IPs suspeitos</h2>
                            <p>3 ou mais falhas no período</p>
                        </div>
                        <div class="au-table-wrap">
                            <table class="au-table" id="tabela-ips">
                                <thead>
                                    <tr>
                                        <th>IP (hash)</th>
                                        <th>Falhas</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <section class="au-card">
                    <div class="au-card-head">
                        <div>
                            <h2>Tentativas de login</h2>
                            <p>Login e IP são armazenados como hash por privacidade.</p>
                        </div>
                        <span class="au-chip" id="as-total-lista">—</span>
                    </div>

                    <form class="au-search as-filters" id="form-filtros">
                        <select name="dias" id="as-dias" aria-label="Período">
                            <option value="1">Últimas 24h</option>
                            <option value="7" selected>Últimos 7 dias</option>
                            <option value="30">Últimos 30 dias</option>
                            <option value="90">Últimos 90 dias</option>
                        </select>
                        <select name="sucesso" id="as-sucesso" aria-label="Resultado">
                            <option value="">Todos</option>
                            <option value="1">Somente sucessos</option>
                            <option value="0">Somente falhas</option>
                        </select>
                        <select name="motivo" id="as-motivo" aria-label="Motivo">
                            <option value="">Todos os motivos</option>
                            <option value="senha_invalida">Senha inválida</option>
                            <option value="captcha_fail">reCAPTCHA falhou</option>
                            <option value="captcha_vazio">reCAPTCHA vazio</option>
                            <option value="bloqueado">Bloqueado (rate limit)</option>
                            <option value="sem_permissao">Sem permissão</option>
                            <option value="auth_fail">Falha na API</option>
                            <option value="ok_local">Sucesso local</option>
                            <option value="ok_frutag">Sucesso Frutag</option>
                        </select>
                        <input type="search" name="login" id="as-login" placeholder="Filtrar por login..." autocomplete="off">
                        <button type="submit">Filtrar</button>
                    </form>

                    <div class="au-table-wrap">
                        <table class="au-table" id="tabela-tentativas">
                            <thead>
                                <tr>
                                    <th>Data/hora</th>
                                    <th>Login (hash)</th>
                                    <th>IP (hash)</th>
                                    <th>Resultado</th>
                                    <th>Motivo</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="as-pagination" id="as-pagination"></div>
                </section>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/admin_seguranca.js?v=1"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
