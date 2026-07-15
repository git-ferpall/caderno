<?php
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/../configuracao/usuarios_local.php'; // conexão local + helpers de perfil

$payload = verify_jwt();

$id   = (int)($payload['sub'] ?? 0);
$tipo = $payload['tipo'] ?? '';
$extra = [];

// Perfil efetivo (admin / representante / usuario) para itens condicionais do menu
$menuPerfil = $id ? (usuarioPerfil($mysqli, $id) ?? 'usuario') : 'usuario';

// Impersonação ativa? (admin/representante vendo o caderno de outro usuário)
$impersonadoPor = $payload['imp_by'] ?? null;

if ($id && $tipo === 'local') {
    // Usuário local: dados vêm do banco do Caderno
    $uLocal = usuarioBuscarPorId($mysqli, $id);
    if ($uLocal) {
        $extra = [
            'cli_empresa'      => $uLocal['nome'],
            'cli_razao_social' => $uLocal['nome'],
            'cli_cnpj_cpf'     => $uLocal['email'] ?: $uLocal['login'],
        ];
    }
} elseif ($id && $tipo) {
    require_once __DIR__ . '/../configuracao/conexao_frutag.php';
    try {
        if ($tipo === 'cliente') {
            $st = $pdo_frutag->prepare("SELECT cli_empresa, cli_razao_social, cli_cnpj_cpf 
                                        FROM cliente 
                                        WHERE cli_cod = :id 
                                        LIMIT 1");
            $st->execute([':id'=>$id]);
            $extra = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        } elseif ($tipo === 'usuario') {
            $st = $pdo_frutag->prepare("SELECT usu_nome AS cli_empresa, usu_nome AS cli_razao_social, usu_cpf AS cli_cnpj_cpf 
                                        FROM usuario 
                                        WHERE usu_cod = :id 
                                        LIMIT 1");
            $st->execute([':id'=>$id]);
            $extra = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Throwable $e) {
        error_log("Erro banco remoto: ".$e->getMessage());
    }
}

$info = [
    'empresa'      => $extra['cli_empresa']     ?? $payload['empresa']      ?? 'Empresa não encontrada',
    'razao_social' => $extra['cli_razao_social']?? $payload['razao_social'] ?? 'Razão Social não encontrada',
    'cpf_cnpj'     => $extra['cli_cnpj_cpf']    ?? $payload['cpf_cnpj']     ?? 'CPF/CNPJ não encontrado',
];



// Busca a propriedade ativa no banco local
$user_id = !empty($user_id) ? $user_id : $id;
$propAtiva = null;
if (!empty($user_id)) {
    $stmt = $mysqli->prepare("
        SELECT endereco_cidade, endereco_uf, nome_razao 
        FROM propriedades 
        WHERE user_id = ? AND ativo = 1 
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $propAtiva = $res->fetch_assoc();
    $stmt->close();
}
?>



<?php if ($impersonadoPor): ?>
<div class="impersonacao-banner">
    <span>Você está vendo o caderno de <strong><?= htmlspecialchars($extra['cli_razao_social'] ?? $payload['name'] ?? ('usuário #' . $id)) ?></strong></span>
    <a href="/funcoes/admin/voltar_impersonacao.php" class="impersonacao-voltar">Voltar ao meu perfil</a>
</div>
<?php endif; ?>

<header class="menu-principal">
    <nav class="navbar nav-menu">
        <div class="nav-logo">
            <a href="/home/"><img src="../img/logo-color.png" alt="Logo Caderno de Campo Frutag"></a>
        </div>
        <div class="nav-items">
            <button class="nav-menu-btn main-btn" id="btn-menu" name="menu" type="button" onclick="abrirMenu()">Menu</button>
        </div>
    </nav>

    <div class="menu-content">
        <div class="user-settings mobile-only">
            <div class="user">
                <h5 class="user-type"><?= htmlspecialchars($info['empresa']) ?></h5>
                <h5 class="user-name"><?= htmlspecialchars($info['razao_social']) ?></h5>
                <h5 class="user-id"><?= htmlspecialchars($info['cpf_cnpj']) ?></h5>
            </div>

            <div class="propriedade">
                <h5 class="user-type">Propriedade Atual</h5>
                <?php if (!empty($propAtiva)) : ?>
                    <h4 class="user-name"><?= htmlspecialchars($propAtiva['endereco_cidade']) ?></h4>
                    <h4 class="user-name"><?= htmlspecialchars($propAtiva['endereco_uf']) ?></h4>
                <?php else : ?>
                    <h4 class="user-name" style="color: #ff4444;">⚠️ Nenhuma propriedade cadastrada</h4>
                    <h5 class="user-id">Favor cadastrar uma propriedade para continuar.</h5>
                <?php endif; ?>
            </div>
        </div>


        <div class="menu-list">
            <ul class="menu-links">
                <a href="/home/apontamento"><li class="menu-link fundo-verde">
                    <div class="btn-icon icon-plus cor-branco"></div>
                    <span class="link-title cor-branco">Novo Apontamento</span>
                </li></a>
                <button type="button" class="alt-propriedade" onclick='altProp()'><li class="menu-link fundo-laranja">
                    <div class="btn-icon icon-pen cor-branco"></div>
                    <span class="link-title cor-branco">Alterar Propriedade</span>
                </li></button>
                <a href="/home/"><li class="menu-link fundo-preto">
                    <div class="btn-icon icon-home cor-branco"></div>
                    <span class="link-title cor-branco">Tela Inicial</span>
                </li></a>
                <a href="/home/silo"><li class="menu-link fundo-azul">
                    <div class="btn-icon icon-silo cor-branco"></div>
                    <span class="link-title cor-branco">Silo de Dados</span>
                </li></a>
                <a href="/home/perfil"><li class="menu-link">
                    <div class="btn-icon icon-user"></div>
                    <span class="link-title">Dados Pessoais</span>
                </li></a>
                <a href="/home/propriedade"><li class="menu-link">
                    <div class="btn-icon icon-pin"></div>
                    <span class="link-title">Cadastro de Propriedade</span>
                </li></a>
                <a href="/home/produtos"><li class="menu-link">
                    <div class="btn-icon icon-fruit"></div>
                    <span class="link-title">Produtos Cultivados</span>
                </li></a>
                <a href="/home/areas"><li class="menu-link">
                    <div class="btn-icon icon-plant"></div>
                    <span class="link-title">Áreas Cultivadas</span>
                </li></a>
                <a href="/home/maquinas"><li class="menu-link">
                    <div class="btn-icon icon-truck"></div>
                    <span class="link-title">Relação de Máquinas</span>
                </li></a>
                <a href="/home/hidroponia"><li class="menu-link">
                    <div class="btn-icon icon-water"></div>
                    <span class="link-title">Hidroponia</span>
                </li></a>
                <a href="/home/relatorios"><li class="menu-link">
                    <div class="btn-icon icon-pen"></div>
                    <span class="link-title">Relatórios</span>
                </li></a>
                <a href="/home/ia_fitossanitaria"><li class="menu-link fundo-verde">
                    <div class="btn-icon icon-plant cor-branco"></div>
                    <span class="link-title cor-branco">IA Fitossanitária</span>
                </li></a>
                <a href="/home/timeline"><li class="menu-link">
                    <div class="btn-icon icon-file"></div>
                    <span class="link-title">Linha do tempo</span>
                </li></a>
                <a href="#" id="btn-offline-prepare" class="d-none" role="button" aria-label="Baixar dados para uso offline">
                    <li class="menu-link fundo-azul">
                        <div class="btn-icon icon-file cor-branco"></div>
                        <span class="link-title cor-branco">Baixar para offline</span>
                    </li>
                </a>
                <?php if (in_array($menuPerfil, ['representante', 'admin'], true)): ?>
                <a href="/home/meus_clientes"><li class="menu-link">
                    <div class="btn-icon icon-people"></div>
                    <span class="link-title">Meus Clientes</span>
                </li></a>
                <?php endif; ?>
                <?php if ($menuPerfil === 'admin'): ?>
                <a href="/home/admin_usuarios"><li class="menu-link fundo-preto">
                    <div class="btn-icon icon-people cor-branco"></div>
                    <span class="link-title cor-branco">Painel Administrativo</span>
                </li></a>
                <?php endif; ?>
            </ul>
        </div>

        <div class="menu-final">
            <img src="../img/logo-frutag.png" alt="Logo da Frutag" class="menu-logo">
            <a href="/configuracao/logout.php" class="nav-menu-btn main-btn fundo-vermelho" onclick="return window.OfflineSession ? OfflineSession.clearBeforeLogout(event) : true">
                <div class="btn-icon icon-exit"></div>
                <span class="link-title">Sair</span>
            </a>
        </div>
    </div>
</header>