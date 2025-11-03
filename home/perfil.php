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

    <?php

    // Aqui vai uma função pra pegar o cadastro já feito do usuário que, caso possua algum dado já informado, esse valor já é colocado automaticamente no campo passível de edição

    $nome = "";
    $email = "";
    $cpf = "";
    $dtnasc = "";
    $num = "";
    $num2 = "";
    $senhaNova = "";

    ?>

    <div id="conteudo">
        <?php include '../include/menu.php' ?>

        <main id="perfil" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Dados Pessoais</h2>
            </div>

            <div class="sistema-main">
                <form action="perfil.php" class="main-form container" id="perf-form">
                    <div class="form-campo">
                        <label for="pf-nome">Nome Completo (não abreviar)</label>
                        <input class="form-text" type="text" name="pfnome" id="pf-nome" placeholder="Seu nome completo" value="<?php echo $nome ?>" required>
                    </div>

                    <div class="form-campo">
                        <label for="pf-email">E-mail</label>
                        <input class="form-text" type="email" name="pfemail" id="pf-email" placeholder="Seu endereço de e-mail" value="<?php echo $email ?>" required>
                    </div>

                    <!--<div class="form-campo">
                        <label for="pf-cpf">CPF</label>
                        <input class="form-text only-num" type="text" name="pfcpf" id="pf-cpf" placeholder="123.456.789-10" maxlength="14" value="<?php echo $cpf ?>" required>
                    </div> -->
                    
                    <!--<div class="form-campo">
                        <label for="pf-nasc">Data de Nascimento</label>
                        <input class="form-text only-num" type="date" name="pfnasc" id="pf-nasc" value="<?php echo $dtnasc ?>" required>
                    </div> -->

                    <div class="form-campo">
                        <label for="pf-num1">Número de Telefone</label>
                        <div class="form-box">
                            <input class="form-text form-tel only-num" type="tel" name="pfnum1" id="pf-num1" placeholder="(DDD) + Número" maxlength="15" value="<?php echo $num ?>" required>
                        </div>
                    </div>
                    <div class="form-campo">
                    <label>Preferências de Contato</label>
                    <div class="form-box" style="display: flex; gap: 20px; align-items: center;">
                        <label>
                        <input type="checkbox" name="aceita_email" id="aceita_email" value="1">
                        Aceito receber e-mails
                        </label>
                        <label>
                        <input type="checkbox" name="aceita_sms" id="aceita_sms" value="1">
                        Aceito receber SMS
                        </label>
                    </div>
                    </div>

                    
                    <!--<div class="form-campo">
                        <label for="pf-num2">Número de Telefone Secundário</label>
                        <div class="form-box">
                            <input class="form-text form-tel only-num" type="tel" name="pfnum2" id="pf-num2" placeholder="(DDD) + Número" maxlength="15" value="<?php echo $num2 ?>" required>
                        </div>
                    </div> -->
                     <!--   
                    <div class="form-campo">
                        <div class="form-center">
                            <button class="main-btn btn-alter" type="button">Alterar Senha</button>
                        </div>
                        <div class="form-alt-box">
                            <div class="form-alt">
                                <div class="form-campo">
                                    <label for="pf-pass-old">Senha Antiga</label>
                                    <div class="form-senha">
                                        <input class="form-text" type="password" name="pfpass-old" id="pf-pass-old" placeholder="Digite sua senha antiga">
                                        <button type="button" class="toggle-senha" onclick="toggleSenha(this)">Ver</button>
                                    </div>
                                </div>
                                <div class="form-campo">
                                    <label for="pf-pass">Senha Nova</label>
                                    <div class="form-senha">
                                        <input class="form-text" type="password" name="pfpass" id="pf-pass" placeholder="Digite sua senha nova" value="<?php echo $senhaNova ?>">
                                        <button type="button" class="toggle-senha" onclick="toggleSenha(this)">Ver</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->

                    <div class="form-submit">
                        <button class="main-btn fundo-vermelho form-cancel" id="form-cancel-perfil" type="button">
                            <!-- <div class="btn-icon icon-x cor-vermelho"></div> -->
                            <span class="main-btn-text">Cancelar</span>
                        </button>
                        <button class="main-btn fundo-verde form-save" id="form-save-perfil" type="button">
                            <!-- <div class="btn-icon icon-check cor-verde"></div> -->
                            <span class="main-btn-text">Salvar</span>
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/contato_cliente.js"></script>

    </div>
        
    <?php include '../include/footer.php' ?>
</body>
</html>