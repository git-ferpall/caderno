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
    <link rel="stylesheet" href="../css/custom/admin-seguranca.css?v=3">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body>
    <?php require '../include/loading.php' ?>
    <?php include '../include/popups.php' ?>

    <div id="conteudo">
        <?php include '../include/menu.php' ?>

        <main class="sistema fundo-img sec-page">
            <div class="container sec-shell">

                <header class="sec-top">
                    <div>
                        <h1>Segurança</h1>
                        <p>Tentativas de login e proteção contra força bruta</p>
                    </div>
                    <button type="button" class="sec-refresh" id="sec-refresh" title="Atualizar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
                        Atualizar
                    </button>
                </header>

                <div class="sec-panel" id="sec-panel">

                    <div class="sec-stats">
                        <div class="sec-stat">
                            <span class="sec-stat-num" id="as-total">0</span>
                            <span class="sec-stat-lbl">Total <em id="as-resumo-periodo">7 dias</em></span>
                        </div>
                        <div class="sec-stat sec-stat--ok">
                            <span class="sec-stat-num" id="as-sucessos">0</span>
                            <span class="sec-stat-lbl">Sucessos</span>
                        </div>
                        <div class="sec-stat sec-stat--fail">
                            <span class="sec-stat-num" id="as-falhas">0</span>
                            <span class="sec-stat-lbl">Falhas</span>
                        </div>
                        <div class="sec-stat sec-stat--rate">
                            <span class="sec-stat-num" id="as-taxa">0%</span>
                            <span class="sec-stat-lbl">Taxa de sucesso</span>
                        </div>
                    </div>

                    <div class="sec-split">
                        <div class="sec-block">
                            <h2>Falhas por motivo</h2>
                            <div id="sec-motivos" class="sec-block-body"></div>
                        </div>
                        <div class="sec-block">
                            <div class="sec-block-title">
                                <h2>IPs suspeitos</h2>
                                <span class="sec-tag" id="as-ips-count">0</span>
                            </div>
                            <p class="sec-hint">3+ falhas no período</p>
                            <div id="sec-ips" class="sec-block-body"></div>
                        </div>
                    </div>

                    <div class="sec-block sec-block--table" id="card-lista">
                        <div class="sec-block-title">
                            <div>
                                <h2>Tentativas de login</h2>
                                <p class="sec-hint">Login e IP armazenados como hash</p>
                            </div>
                            <span class="sec-tag" id="as-total-lista">0</span>
                        </div>

                        <form class="sec-filters" id="form-filtros">
                            <select name="dias" id="as-dias" aria-label="Período">
                                <option value="1">24 horas</option>
                                <option value="7" selected>7 dias</option>
                                <option value="30">30 dias</option>
                                <option value="90">90 dias</option>
                            </select>
                            <select name="sucesso" id="as-sucesso" aria-label="Resultado">
                                <option value="">Todos</option>
                                <option value="1">Sucessos</option>
                                <option value="0">Falhas</option>
                            </select>
                            <select name="motivo" id="as-motivo" aria-label="Motivo">
                                <option value="">Motivo</option>
                                <option value="senha_invalida">Senha inválida</option>
                                <option value="captcha_fail">reCAPTCHA</option>
                                <option value="captcha_vazio">reCAPTCHA vazio</option>
                                <option value="bloqueado">Rate limit</option>
                                <option value="sem_permissao">Sem permissão</option>
                                <option value="auth_fail">Falha API</option>
                                <option value="ok_local">OK local</option>
                                <option value="ok_frutag">OK Frutag</option>
                            </select>
                            <input type="search" name="login" id="as-login" placeholder="Buscar login…" autocomplete="off">
                            <button type="submit" class="sec-btn-filter">Filtrar</button>
                        </form>

                        <div class="sec-table-wrap">
                            <table class="sec-table" id="tabela-tentativas">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Login</th>
                                        <th>IP</th>
                                        <th>Status</th>
                                        <th>Motivo</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="sec-pages" id="as-pagination"></div>
                    </div>

                </div>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/admin_seguranca.js?v=3"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
