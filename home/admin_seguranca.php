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

        <main class="sistema fundo-img sec-page">
            <div class="container sec-shell">

                <header class="sec-hero">
                    <div class="sec-hero-text">
                        <div class="sec-eyebrow">
                            <span class="sec-eyebrow-dot" aria-hidden="true"></span>
                            Monitoramento ativo
                        </div>
                        <h1>Segurança e auditoria</h1>
                        <p>Acompanhe tentativas de login, detecte padrões suspeitos e monitore a proteção contra força bruta em tempo real.</p>
                    </div>
                    <div class="sec-hero-actions">
                        <button type="button" class="sec-btn" id="sec-refresh" title="Atualizar dados">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 12a9 9 0 1 1-2.64-6.36"/>
                                <path d="M21 3v6h-6"/>
                            </svg>
                            Atualizar
                        </button>
                    </div>
                </header>

                <div class="sec-kpis" id="sec-kpis">
                    <article class="sec-kpi sec-kpi--total">
                        <div class="sec-kpi-top">
                            <div>
                                <div class="sec-kpi-label">Total</div>
                            </div>
                            <div class="sec-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                            </div>
                        </div>
                        <div class="sec-kpi-value" id="as-total">—</div>
                        <div class="sec-kpi-foot" id="as-resumo-periodo">Últimos 7 dias</div>
                    </article>

                    <article class="sec-kpi sec-kpi--ok">
                        <div class="sec-kpi-top">
                            <div class="sec-kpi-label">Sucessos</div>
                            <div class="sec-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg>
                            </div>
                        </div>
                        <div class="sec-kpi-value" id="as-sucessos">—</div>
                        <div class="sec-kpi-foot">Logins autorizados</div>
                    </article>

                    <article class="sec-kpi sec-kpi--fail">
                        <div class="sec-kpi-top">
                            <div class="sec-kpi-label">Falhas</div>
                            <div class="sec-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </div>
                        </div>
                        <div class="sec-kpi-value" id="as-falhas">—</div>
                        <div class="sec-kpi-foot">Tentativas bloqueadas</div>
                    </article>

                    <article class="sec-kpi sec-kpi--rate">
                        <div class="sec-kpi-top">
                            <div class="sec-kpi-label">Taxa de sucesso</div>
                            <div class="sec-kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                            </div>
                        </div>
                        <div class="sec-kpi-value" id="as-taxa">—</div>
                        <div class="sec-kpi-foot">Do total no período</div>
                    </article>
                </div>

                <div class="sec-grid">
                    <section class="sec-card" id="card-motivos">
                        <div class="sec-card-head">
                            <div>
                                <h2>Falhas por motivo</h2>
                                <p>Distribuição das tentativas rejeitadas</p>
                            </div>
                        </div>
                        <div class="sec-card-body" id="sec-motivos"></div>
                    </section>

                    <section class="sec-card" id="card-ips">
                        <div class="sec-card-head">
                            <div>
                                <h2>IPs suspeitos</h2>
                                <p>3 ou mais falhas no período</p>
                            </div>
                            <span class="sec-badge sec-badge--warn" id="as-ips-count">0 alertas</span>
                        </div>
                        <div class="sec-card-body" id="sec-ips"></div>
                    </section>
                </div>

                <section class="sec-card" id="card-lista">
                    <div class="sec-card-head">
                        <div>
                            <h2>Registro de tentativas</h2>
                            <p>Login e IP armazenados como hash por privacidade</p>
                        </div>
                        <span class="sec-badge" id="as-total-lista">—</span>
                    </div>

                    <form class="sec-toolbar" id="form-filtros">
                        <div class="sec-field">
                            <label for="as-dias">Período</label>
                            <select name="dias" id="as-dias">
                                <option value="1">Últimas 24h</option>
                                <option value="7" selected>Últimos 7 dias</option>
                                <option value="30">Últimos 30 dias</option>
                                <option value="90">Últimos 90 dias</option>
                            </select>
                        </div>
                        <div class="sec-field">
                            <label for="as-sucesso">Resultado</label>
                            <select name="sucesso" id="as-sucesso">
                                <option value="">Todos</option>
                                <option value="1">Somente sucessos</option>
                                <option value="0">Somente falhas</option>
                            </select>
                        </div>
                        <div class="sec-field">
                            <label for="as-motivo">Motivo</label>
                            <select name="motivo" id="as-motivo">
                                <option value="">Todos</option>
                                <option value="senha_invalida">Senha inválida</option>
                                <option value="captcha_fail">reCAPTCHA falhou</option>
                                <option value="captcha_vazio">reCAPTCHA vazio</option>
                                <option value="bloqueado">Rate limit</option>
                                <option value="sem_permissao">Sem permissão</option>
                                <option value="auth_fail">Falha na API</option>
                                <option value="ok_local">Sucesso local</option>
                                <option value="ok_frutag">Sucesso Frutag</option>
                            </select>
                        </div>
                        <div class="sec-field sec-field--wide">
                            <label for="as-login">Login</label>
                            <input type="search" name="login" id="as-login" placeholder="Filtrar por usuário..." autocomplete="off">
                        </div>
                        <button type="submit" class="sec-filter-btn">Aplicar filtros</button>
                    </form>

                    <div class="sec-card-body">
                        <div class="sec-table-wrap">
                            <table class="sec-table" id="tabela-tentativas">
                                <thead>
                                    <tr>
                                        <th>Data / hora</th>
                                        <th>Login</th>
                                        <th>IP</th>
                                        <th>Status</th>
                                        <th>Motivo</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="sec-pagination" id="as-pagination"></div>
                    </div>
                </section>

            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/admin_seguranca.js?v=2"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
