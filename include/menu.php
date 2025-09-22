<?php
// 1) Funções (define sec_session_start, isLogged, verificaSessaoExpirada, etc.)
require_once __DIR__ . '/../configuracao/configuracao_funcoes.php';

// 2) Conexão e demais dependências
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../funcoes/busca_usuario.php';
require_once __DIR__ . '/../funcoes/busca_propriedade.php';

// 3) Garante sessão ativa (idempotente)
if (function_exists('sec_session_start')) {
    sec_session_start();
} else {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // fallback simples se, por algum motivo, o arquivo de funções não carregou
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// (Opcional, mas recomendado)
if (function_exists('verificaSessaoExpirada')) {
    verificaSessaoExpirada(); // expira após inatividade
}

// (Opcional, se este arquivo exige usuário logado)
if (function_exists('isLogged') && !isLogged()) {
    header('Location: /login.php?e=session');
    exit();
}

// 4) Carrega dados do usuário/propriedade
$cod_usuario = $_SESSION['cliente_cod'] ?? null;
$usuario     = $cod_usuario ? buscarUsuarioPorCodigo($cod_usuario, $mysqli) : null;
$propriedade = $cod_usuario ? buscarPropriedadePorUsuario($cod_usuario, $mysqli) : null;

?>

<header class="menu-principal">
    <nav class="navbar nav-menu">
        <div class="nav-logo">
            <a href="home.php"><img src="../img/logo-color.png" alt="Logo Caderno de Campo Frutag"></a>
        </div>
        <div class="nav-items">
            <button class="nav-menu-btn main-btn" id="btn-menu" name="menu" type="button" onclick="abrirMenu()">Menu</button>
        </div>
    </nav>

    <div class="menu-content">
        <div class="user-settings mobile-only">
            <div class="user">
                <h5 class="user-type">
                    <?= isset($usuario['cli_empresa']) ? htmlspecialchars($usuario['cli_empresa']) : 'Empresa não encontrada'; ?>
                </h5>
                <h5 class="user-name">
                    <?= isset($usuario['cli_razao_social']) ? htmlspecialchars($usuario['cli_razao_social']) : 'Razão Social não Encontrada'; ?>
                </h5>
                <h5 class="user-id">
                    <?= isset($usuario['cli_cnpj_cpf']) ? htmlspecialchars($usuario['cli_cnpj_cpf']) : 'CPF/CNPJ não Encontrado'; ?>
                </h5>
                
            </div>
            <div class="propriedade">
                <h5 class="user-type">Propriedade Atual</h5>
                <h4 class="user-name">Nome da Cidade, UF</h4>
            </div>
        </div>

        <div class="menu-list">
            <ul class="menu-links">
                <a href="./apontamento.php"><li class="menu-link fundo-verde">
                    <div class="btn-icon icon-plus cor-branco"></div>
                    <span class="link-title cor-branco">Novo Apontamento</span>
                </li></a>
                <button type="button" class="alt-propriedade" onclick='altProp()'><li class="menu-link fundo-laranja">
                    <div class="btn-icon icon-pen cor-branco"></div>
                    <span class="link-title cor-branco">Alterar Propriedade</span>
                </li></button>
                <a href="./home.php"><li class="menu-link fundo-preto">
                    <div class="btn-icon icon-home cor-branco"></div>
                    <span class="link-title cor-branco">Tela Inicial</span>
                </li></a>
                <a href="./silo.php"><li class="menu-link fundo-azul">
                    <div class="btn-icon icon-silo cor-branco"></div>
                    <span class="link-title cor-branco">Silo de Dados</span>
                </li></a>
                <a href="./perfil.php"><li class="menu-link">
                    <div class="btn-icon icon-user"></div>
                    <span class="link-title">Dados Pessoais</span>
                </li></a>
                <a href="./propriedade.php"><li class="menu-link">
                    <div class="btn-icon icon-pin"></div>
                    <span class="link-title">Dados da Propriedade</span>
                </li></a>
                <a href="./produtos.php"><li class="menu-link">
                    <div class="btn-icon icon-fruit"></div>
                    <span class="link-title">Produtos Cultivados</span>
                </li></a>
                <a href="./areas.php"><li class="menu-link">
                    <div class="btn-icon icon-plant"></div>
                    <span class="link-title">Áreas Cultivadas</span>
                </li></a>
                <a href="./maquinas.php"><li class="menu-link">
                    <div class="btn-icon icon-truck"></div>
                    <span class="link-title">Relação de Máquinas</span>
                </li></a>
                <a href="./hidroponia.php"><li class="menu-link">
                    <div class="btn-icon icon-water"></div>
                    <span class="link-title">Hidroponia</span>
                </li></a>
                <a href="./relatorios.php"><li class="menu-link">
                    <div class="btn-icon icon-pen"></div>
                    <span class="link-title">Relatórios</span>
                </li></a>
                <a href="./clientes.php"><li class="menu-link">
                    <div class="btn-icon icon-people"></div>
                    <span class="link-title">Painel de Clientes</span>
                </li></a>
            </ul>
        </div>

        <div class="menu-final">
            <img src="../img/logo-frutag.png" alt="Logo da Frutag" class="menu-logo">
            <button class="nav-menu-btn main-btn fundo-vermelho" id="btn-sair" type="button" onclick="sair()">
                <div class="btn-icon icon-exit"></div>
                <span class="link-title">Sair</span>
            </button>
        </div>
    </div>
</header>