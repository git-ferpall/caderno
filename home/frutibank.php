<?php
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../funcoes/frutibank/helpers.php';

$fbUser = $GLOBALS['auth_user'] ?? null;
$fbUserId = (int)($fbUser->sub ?? 0);

if (!$fbUserId || !frutibankHabilitado($mysqli, $fbUserId)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Acesso negado</title></head><body style="font-family:sans-serif;padding:40px;text-align:center"><h1>Acesso negado</h1><p>O Frutibank ainda não foi liberado para o seu usuário. Fale com o administrador.</p><p><a href="/home">Voltar</a></p></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frutibank — Caderno Frutag</title>
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
                    <h1 class="fb-logo-wrap"><img src="../img/frutibank-logo.png" alt="Frutibank" class="fb-logo"></h1>
                    <p>Gere cobranças PIX com QR Code em formato de boleto para entregar aos seus clientes. Cadastre sua chave PIX, registre os clientes por CPF ou CNPJ e imprima a cobrança.</p>
                </header>

                <nav class="fb-tabs" id="fb-tabs" role="tablist">
                    <button type="button" class="fb-tab" data-tab="chave" role="tab">
                        Chave PIX
                        <span class="fb-tab-chip" id="fb-chip-config">—</span>
                    </button>
                    <button type="button" class="fb-tab" data-tab="clientes" role="tab">
                        Clientes
                        <span class="fb-tab-chip" id="fb-chip-clientes">—</span>
                    </button>
                    <button type="button" class="fb-tab" data-tab="cobrancas" role="tab">
                        Cobranças
                        <span class="fb-tab-chip" id="fb-chip-cobrancas">—</span>
                    </button>
                </nav>

                <section class="au-card fb-panel" data-panel="chave">
                    <div class="au-card-head">
                        <div>
                            <h2>Minha chave PIX</h2>
                            <p>Os pagamentos caem direto na sua conta. Nome e cidade aparecem no aplicativo do pagador (exigência do padrão PIX).</p>
                        </div>
                    </div>

                    <!-- Chave já cadastrada: modo visualização -->
                    <div class="fb-config-view d-none" id="fb-config-view">
                        <div class="fb-view-grid">
                            <div class="fb-view-item">
                                <label>Tipo da chave</label>
                                <strong id="fb-view-tipo">—</strong>
                            </div>
                            <div class="fb-view-item">
                                <label>Chave PIX</label>
                                <strong id="fb-view-chave">—</strong>
                            </div>
                            <div class="fb-view-item">
                                <label>Nome do recebedor</label>
                                <strong id="fb-view-nome">—</strong>
                            </div>
                            <div class="fb-view-item">
                                <label>Cidade</label>
                                <strong id="fb-view-cidade">—</strong>
                            </div>
                        </div>
                        <div class="fb-config-view-actions">
                            <span class="fb-view-ok">Chave PIX ativa — pronta para gerar cobranças</span>
                            <button type="button" class="main-btn fundo-laranja" id="fb-btn-editar-config">Editar chave PIX</button>
                        </div>
                    </div>

                    <!-- Cadastro / edição -->
                    <div class="fb-chave-editor" id="fb-chave-editor">
                        <form class="au-form" id="form-config">
                            <div class="au-field">
                                <label for="fb-tipo">Tipo da chave</label>
                                <select id="fb-tipo" name="tipo_chave">
                                    <option value="cpf">CPF</option>
                                    <option value="cnpj">CNPJ</option>
                                    <option value="email">E-mail</option>
                                    <option value="telefone">Telefone (celular)</option>
                                    <option value="aleatoria" selected>Aleatória</option>
                                </select>
                            </div>
                            <div class="au-field">
                                <label for="fb-chave">Chave PIX</label>
                                <input type="text" id="fb-chave" name="chave_pix" placeholder="Sua chave PIX" required autocomplete="off">
                                <small class="fb-hint" id="fb-chave-hint"></small>
                            </div>
                            <div class="au-field au-field-wide">
                                <label for="fb-nome">Nome do recebedor <small>(máx. 25)</small></label>
                                <input type="text" id="fb-nome" name="nome_recebedor" maxlength="25" placeholder="Ex.: Maria da Silva" required>
                            </div>
                            <div class="au-field">
                                <label for="fb-uf">Estado</label>
                                <select id="fb-uf" name="uf" required>
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                            <div class="au-field">
                                <label for="fb-cidade">Cidade</label>
                                <select id="fb-cidade" name="cidade" required>
                                    <option value="">Selecione o estado primeiro...</option>
                                </select>
                            </div>
                            <div class="au-form-actions">
                                <button type="button" class="main-btn fundo-vermelho d-none" id="fb-btn-cancelar-config">Cancelar</button>
                                <button type="submit" class="main-btn fundo-verde">Salvar chave PIX</button>
                            </div>
                        </form>

                        <aside class="fb-pix-preview" id="fb-pix-preview">
                            <span class="fb-pix-preview-titulo">Como o pagador verá no app do banco</span>
                            <div class="fb-pix-preview-linha"><label>Recebedor</label><strong id="fb-prev-nome">—</strong></div>
                            <div class="fb-pix-preview-linha"><label>Cidade</label><strong id="fb-prev-cidade">—</strong></div>
                            <div class="fb-pix-preview-linha"><label>Chave</label><strong id="fb-prev-chave">—</strong></div>
                        </aside>
                    </div>
                </section>

                <section class="au-card fb-panel" data-panel="clientes">
                    <div class="au-card-head au-accent-verde">
                        <div>
                            <h2>Clientes de cobrança</h2>
                            <p>Cadastre quem você vai cobrar, por CPF ou CNPJ.</p>
                        </div>
                    </div>

                    <form class="au-form" id="form-cliente">
                        <div class="au-field au-field-wide">
                            <label for="fb-cli-doc">CPF ou CNPJ</label>
                            <div class="fb-doc-busca">
                                <input type="text" id="fb-cli-doc" name="cpf_cnpj" placeholder="Somente números" inputmode="numeric" maxlength="18" required>
                                <button type="button" class="main-btn fundo-azul" id="fb-btn-receita" disabled>Buscar na Receita</button>
                            </div>
                            <small class="fb-hint" id="fb-receita-info">Para CNPJ, buscamos a razão social direto na Receita Federal. Para CPF, preencha o nome manualmente.</small>
                        </div>
                        <div class="au-field au-field-wide">
                            <label for="fb-cli-nome">Nome do cliente</label>
                            <input type="text" id="fb-cli-nome" name="nome" placeholder="Ex.: João Pereira" required>
                        </div>
                        <div class="au-field au-field-wide">
                            <label for="fb-cli-tel">WhatsApp <small>(opcional, para enviar as cobranças)</small></label>
                            <input type="text" id="fb-cli-tel" name="telefone" placeholder="(00) 90000-0000" inputmode="tel" maxlength="16">
                        </div>
                        <div class="au-form-actions">
                            <button type="submit" class="main-btn fundo-verde">Cadastrar cliente</button>
                        </div>
                    </form>

                    <div class="au-table-wrap">
                        <table class="au-table fb-table-clientes" id="tabela-fb-clientes">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>CPF / CNPJ</th>
                                    <th>Cobranças</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>

                <section class="au-card fb-panel" data-panel="cobrancas">
                    <div class="au-card-head">
                        <div>
                            <h2>Cobranças</h2>
                            <p>Escolha o cliente, informe o valor e gere a cobrança PIX pronta para imprimir.</p>
                        </div>
                    </div>

                    <form class="au-form" id="form-cobranca">
                        <div class="au-field au-field-wide">
                            <label for="fb-cob-cliente">Cliente</label>
                            <select id="fb-cob-cliente" name="cliente_id" required>
                                <option value="">Selecione um cliente...</option>
                            </select>
                        </div>
                        <div class="au-field">
                            <label for="fb-cob-valor">Valor (R$)</label>
                            <input type="text" id="fb-cob-valor" name="valor" placeholder="Ex.: 150,00" inputmode="decimal" required>
                        </div>
                        <div class="au-field">
                            <label for="fb-cob-venc">Vencimento <small>(opcional)</small></label>
                            <input type="date" id="fb-cob-venc" name="vencimento">
                        </div>
                        <div class="au-field au-field-wide">
                            <label for="fb-cob-desc">Descrição <small>(opcional, aparece para o pagador)</small></label>
                            <input type="text" id="fb-cob-desc" name="descricao" maxlength="140" placeholder="Ex.: Caixa de uvas - pedido 123">
                        </div>
                        <div class="au-form-actions">
                            <button type="submit" class="main-btn fundo-verde">Gerar cobrança</button>
                        </div>
                    </form>

                    <div class="au-table-wrap">
                        <table class="au-table" id="tabela-fb-cobrancas">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
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
        <script src="../js/frutibank.js"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
