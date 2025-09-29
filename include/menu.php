<?php
    // Pega token do AUTH_COOKIE ou do cookie "token"
    $bearer = $_COOKIE[AUTH_COOKIE] ?? ($_COOKIE['token'] ?? '');

    $ch = curl_init('https://caderno.frutag.com.br/sso/userinfo.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $bearer],
    ]);
    $resp = curl_exec($ch);

    // DEBUG: loga a resposta bruta do endpoint userinfo
    error_log("USERINFO RESP: " . $resp);

    $info = json_decode($resp, true);

    // Só zera se realmente falhou
    if (!is_array($info) || !isset($info['ok']) || $info['ok'] !== true) {
        $info = [
            'empresa'      => null,
            'razao_social' => null,
            'cpf_cnpj'     => null
        ];
    }


    $propAtiva = null;
    if ($user_id) {
        $stmt = $mysqli->prepare("SELECT endereco_cidade, endereco_uf, nome_razao 
                                FROM propriedades 
                                WHERE user_id = ? AND ativo = 1 
                                LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $propAtiva = $res->fetch_assoc();
        $stmt->close();
    }
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
                <h5 class="user-type"><?= htmlspecialchars($info['empresa'] ?? 'Empresa não encontrada'); ?></h5>
                <h5 class="user-name"><?= htmlspecialchars($info['razao_social'] ?? 'Razão Social não encontrada'); ?></h5>
                <h5 class="user-id"><?= htmlspecialchars($info['cpf_cnpj'] ?? 'CPF/CNPJ não encontrado'); ?></h5>

            </div>
            <div class="propriedade">
                <h5 class="user-type">Propriedade Atual</h5>
                <h4 class="user-name"><?= htmlspecialchars($propAtiva['endereco_cidade']) ?> </h4>
                <h4 class="user-name"><?= htmlspecialchars($propAtiva['endereco_uf']) ?> </h4>
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
            <a href="/configuracao/logout.php" class="nav-menu-btn main-btn fundo-vermelho">
                <div class="btn-icon icon-exit"></div>
                <span class="link-title">Sair</span>
            </a>
        </div>
    </div>
</header>