<?php
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../funcoes/carregar_propriedade.php';

$user_id = $_SESSION['user_id'] ?? null;
$propriedades = [];
$nome = $email = $cpf = $cnpj = $ruaEnder = $ufEnder = $numEnder = $cidEnder = $telCom = $telCom2 = "";

if ($user_id) {
    // lista todas
    $propriedades = carregarPropriedades($mysqli, $user_id);

    // se for edição
    if (isset($_GET['editar'])) {
        $prop = carregarPropriedadePorId($mysqli, $user_id, (int) $_GET['editar']);
        if ($prop) {
            $nome     = $prop['nome_razao'];
            $email    = $prop['email'];
            $cnpj     = ($prop['tipo_doc'] === 'cnpj') ? $prop['cpf_cnpj'] : "";
            $cpf      = ($prop['tipo_doc'] === 'cpf') ? $prop['cpf_cnpj'] : "";
            $ruaEnder = $prop['endereco_rua'];
            $ufEnder  = $prop['endereco_uf'];
            $numEnder = $prop['endereco_numero'];
            $cidEnder = $prop['endereco_cidade'];
            $telCom   = $prop['telefone1'];
            $telCom2  = $prop['telefone2'];
        }
    }
}    


?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo - Frutag</title>

    <link rel="stylesheet" href="../css/style.css">

    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body>
    <?php include '../include/loading.php' ?> 
    <?php include '../include/popups.php' ?>

    <div id="conteudo">
        <?php include '../include/menu.php' ?>  
        <main id="propriedade" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Cadastro de Propriedade</h2>
            </div>

            <div class="sistema-main">
                <div class="item-box container">
                    <?php if (!empty($propriedades)): ?>
                        <?php foreach ($propriedades as $prop): ?>
                            <div class="item item-propriedade v2" id="prop-<?php echo (int)$prop['id']; ?>">
                                <h4 class="item-title">
                                    <?php echo htmlspecialchars($prop['nome_razao'] ?? 'Sem nome'); ?>
                                </h4>
                                <div class="item-edit">
                                    <a class="edit-btn" href="propriedade.php?editar=<?php echo (int)$prop['id']; ?>">
                                        Editar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="item-none">Nenhuma propriedade cadastrada.</div>
                    <?php endif; ?>
                </div>


                <form action="/funcoes/salvar_propriedade.php" method="POST" class="main-form container" id="prop-form">

                    <div class="form-campo">
                        <label for="pf-razao">Nome ou Razão Social</label>
                        <input class="form-text" type="text" name="pfrazao" id="pf-razao" 
                            placeholder="Seu nome completo" required
                            value="<?php echo htmlspecialchars($nome); ?>">
                    </div>

                    <div class="form-campo">
                        <label for="pf-cnpj-cpf">Tipo e N° do Documento</label>
                        <div class="form-box" id="pf-cnpj-cpf">
                            <select name="pftipo" id="pf-tipo" class="form-select form-text f1" required>
                                <option value="cnpj" <?php echo ($cnpj !== '') ? 'selected' : ''; ?>>CNPJ</option>
                                <option value="cpf"  <?php echo ($cpf !== '')  ? 'selected' : ''; ?>>CPF</option>
                            </select>

                            <input class="form-text only-num f4" type="text" name="pfcnpj" id="pf-cnpj" 
                                placeholder="12.345.789/0001-10" maxlength="18" 
                                value="<?php echo htmlspecialchars($cnpj); ?>">
                            <input class="form-text only-num f4" type="text" name="pfcpf" id="pf-cpf" 
                                placeholder="123.456.789-10" maxlength="14" 
                                value="<?php echo htmlspecialchars($cpf); ?>">
                        </div>
                    </div>

                    <div class="form-campo">
                        <label for="pf-email-com">E-mail</label>
                        <input class="form-text" type="email" name="pfemail-com" id="pf-email-com"
                            placeholder="Seu e-mail comercial" required
                            value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                        
                    <div class="form-box">
                        <div class="form-campo f5">
                            <label for="pf-ender-rua">Endereço</label>
                            <input class="form-text" type="text" name="pfender-rua" id="pf-ender-rua" 
                                placeholder="Rua, logradouro, etc" required
                                value="<?php echo htmlspecialchars($ruaEnder); ?>">
                        </div>
                        <div class="form-campo f2">
                            <label for="pf-ender-num">N°</label>
                            <input type="text" class="form-text form-num only-num" 
                                name="pfender-num" id="pf-ender-num" placeholder="S/N" maxlength="6" 
                                value="<?php echo htmlspecialchars($numEnder); ?>">
                        </div>
                    </div>

                    <div class="form-box">
                        <div class="form-campo f2">
                            <label for="pf-ender-uf">Estado</label>
                            <select name="pfender-uf" id="pf-ender-uf" class="form-select form-text" required>
                                <option value="">Selecione</option>
                                <option value="AC" <?php if($ufEnder==='AC') echo 'selected'; ?>>AC</option>
                                <option value="SP" <?php if($ufEnder==='SP') echo 'selected'; ?>>SP</option>
                                <option value="PR" <?php if($ufEnder==='PR') echo 'selected'; ?>>PR</option>
                                <!-- Adicione os demais estados -->
                            </select>
                        </div>
                        <div class="form-campo f5">
                            <label for="pf-ender-cid">Cidade</label>
                            <input class="form-text" type="text" name="pfender-cid" id="pf-ender-cid" 
                                placeholder="Cidade" required
                                value="<?php echo htmlspecialchars($cidEnder); ?>">
                        </div>
                    </div>

                    <div class="form-campo">
                        <label for="pf-num1-com">Telefone Comercial</label>
                        <div class="form-box">
                            <input class="form-text form-tel only-num" type="tel" name="pfnum1-com" id="pf-num1-com"
                                placeholder="(DDD) + Número" maxlength="15" 
                                value="<?php echo htmlspecialchars($telCom); ?>">
                        </div>
                    </div>

                    <div class="form-campo">
                        <label for="pf-num2-com">Telefone Comercial Secundário</label>
                        <div class="form-box">
                            <input class="form-text form-tel only-num" type="tel" name="pfnum2-com" id="pf-num2-com"
                                placeholder="(DDD) + Número" maxlength="15" 
                                value="<?php echo htmlspecialchars($telCom2); ?>">
                        </div>
                    </div>

                    <div class="form-submit">
                        <button class="main-btn fundo-vermelho form-cancel" id="form-cancel-propriedade" type="button">
                            <span class="main-btn-text">Cancelar</span>
                        </button>
                        <button class="main-btn fundo-verde form-save" id="form-save-propriedade" type="submit">
                            <span class="main-btn-text">Salvar</span>
                        </button>
                    </div>
                </form>

            </div>
        </main>

        <?php include '../include/imports.php' ?>
    </div>
        
    <?php include '../include/footer.php' ?>
</body>
</html>