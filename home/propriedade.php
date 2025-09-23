<?php
require_once __DIR__ . '/../configuracao/protect.php';
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

        <?php

        // Aqui vai uma função pra pegar o cadastro já feito do usuário que, caso possua algum dado já informado, esse valor já é colocado automaticamente no campo passível de edição

        $nome = "";
        $email = "";
        $cpf = "";
        $cnpj = "";
        $ruaEnder = "";
        $ufEnder = "";
        $numEnder = "";
        $cidEnder = "";
        $telCom = "";
        $telCom2 = "";

        ?>

        <main id="propriedade" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Cadastro de Propriedade</h2>
            </div>

            <div class="sistema-main">
                <div class="item-box container">

                    <?php

                    if(!empty($propriedades)){
                        // Aqui vai uma função pra pegar a propriedade selecionada atualmente
                        $propriedade = $propriedades[0]; 
                        echo '
                            <div class="item item-propriedade v2" id="prop-' . $propriedade['id'] . '">
                                <h4 class="item-title">' . $propriedade['nome'] . '</h4>
                                <div class="item-edit">
                                    <button class="edit-btn" id="edit-propriedade" type="button" onclick="altProp()">
                                        Alterar
                                    </button>
                                </div>
                            </div>
                        ';
                    } else {
                        echo '<div class="item-none">Nenhuma propriedade cadastrada.</div>';
                    }
                    
                    ?>
                    
                </div>

                <form action="/funcoes/salvar_propriedade.php" method="POST" class="main-form container" id="prop-form">
                    <div class="form-campo">
                        <label for="pf-razao">Nome ou Razão Social</label>
                        <input type="text" class="form-text" name="pfrazao" id="pf-razao" placeholder="Seu nome completo" value="<?php echo $nome ?>" required>
                    </div>

                    <div class="form-campo">
                        <label for="pf-cnpj-cpf">Tipo e N° do Documento</label>
                        <div class="form-box" id="pf-cnpj-cpf">
                            <select name="pftipo" id="pf-tipo" class="form-select form-text f1" required>
                                <option value="cnpj">CNPJ</option>
                                <option value="cpf">CPF</option>
                            </select>
                            <input class="form-text only-num f4" type="text" name="pfcnpj" id="pf-cnpj" placeholder="12.345.789/0001-10" maxlength="18" value="<?php echo $cnpj ?>">
                            <input class="form-text only-num f4" type="text" name="pfcpf" id="pf-cpf" placeholder="123.456.789-10" maxlength="14" value="<?php echo $cpf ?>">
                        </div>
                    </div>

                    <div class="form-campo">
                        <label for="pf-email-com">E-mail</label>
                        <input class="form-text" type="email" name="pfemail-com" id="pf-email-com" placeholder="Seu e-mail comercial" value="<?php echo $email ?>" required>
                    </div>
                        
                    <div class="form-box">
                        <div class="form-campo f5">
                            <label for="pf-ender-rua">Endereço</label>
                            <input class="form-text" type="text" name="pfender-rua" id="pf-ender-rua" placeholder="Rua, logradouro, etc" value="<?php echo $ruaEnder ?>" required>
                        </div>
                        <div class="form-campo f2">
                            <label for="pf-ender-num">N°</label>
                            <input type="text" class="form-text form-num only-num" name="pfender-num" id="pf-ender-num" placeholder="S/N" maxlength="6" value="<?php echo $numEnder ?>">
                        </div>
                    </div>

                    <div class="form-box">
                        <div class="form-campo f2">
                            <label for="pf-ender-uf">Estado</label>
                            <select name="pfender-uf" id="pf-ender-uf" class="form-select form-text" value="<?php echo $ufEnder ?>" required></select>
                        </div>
                        <div class="form-campo f5">
                            <label for="pf-ender-cid">Cidade</label>
                            <select name="pfender-cid" id="pf-ender-cid" class="form-select form-text" value="<?php echo $cidEnder ?>" required></select>
                        </div>
                    </div>

                    <div class="form-campo">
                        <label for="pf-num1-com">Telefone Comercial</label>
                        <div class="form-box">
                            <input class="form-text form-tel only-num" type="tel" name="pfnum1-com" id="pf-num1-com" placeholder="(DDD) + Número" maxlength="15" value="<?php echo $telCom ?>">
                        </div>
                    </div>

                    <div class="form-campo">
                        <label for="pf-num2-com">Telefone Comercial Secundário</label>
                        <div class="form-box">
                            <input class="form-text form-tel only-num" type="tel" name="pfnum2-com" id="pf-num2-com" placeholder="(DDD) + Número" maxlength="15" value="<?php echo $telCom2 ?>">
                        </div>
                    </div>

                    <div class="form-submit">
                        <button class="main-btn fundo-vermelho form-cancel" id="form-cancel-propriedade" type="button">
                            <!-- <div class="btn-icon icon-x cor-vermelho"></div> -->
                            <span class="main-btn-text">Cancelar</span>
                        </button>
                        <button class="main-btn fundo-verde form-save" id="form-save-propriedade" type="button">
                            <!-- <div class="btn-icon icon-check cor-verde"></div> -->
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