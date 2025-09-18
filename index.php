<?php session_start();
#define('ROOT_PATH', __DIR__ . '/../../login/');

#require_once "/var/www/html/login/configuracao/configuracao_conexao.php";
#require_once "/var/www/html/login/configuracao/configuracao_funcoes.php";

require_once __DIR__ . '/configuracao/auth.php';
$usr = require_login();


?>



<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo - Frutag</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body>

<?php require 'include/loading.php'; ?>

<div id="conteudo" class="content-login">
    <header class="header-login" id="cabecalho">
        <div class="logo-box">
            <a href="index.php"><img src="img/logo-color.png" alt="Logo Caderno de Campo Frutag"></a>
            <a href="https://www.frutag.com.br" target="_blank"><img src="img/logo-frutag.png" alt="Logo Frutag"></a>
        </div>
        <?php if (isset($_SESSION['retorno'])): ?>
            <div class="erro-login" style="color: red; text-align: center; margin-top: 10px;">
                <?= htmlspecialchars($_SESSION['retorno']['mensagem']) ?>
            </div>
            <?php unset($_SESSION['retorno']); ?>
        <?php endif; ?>
    </header>

    <main id="login" class="login">
        <div class="login-box" id="login-box-id">
            <!-- Formulário de Login -->
            <div class="login-content" id="login-form">
                <h2 class="login-title">Faça seu login</h2>
                <form id="flogin" class="main-form" action="configuracao/login_process.php" method="POST">
                    <input class="fcampo" id="fuser" name="login" type="text" placeholder="Digite seu usuário ou email..." required>
                    <input class="fcampo" id="fpass" name="senha" type="password" placeholder="Digite sua senha..." required>
                    <input type="hidden" name="next" value="<?= htmlspecialchars($_GET['next'] ?? '/') ?>">
                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                    <div class="text-center">
                        <button id="fesq" name="esqueci" type="button" onclick="toggleForm('rec')">Esqueceu sua senha? <strong>Clique aqui</strong></button>
                    </div>
                    <div class="fbuttons">
                        <button class="fbotao main-btn" id="fcad" type="button" onclick="toggleForm('cad')">Cadastrar</button>
                        <input class="fbotao main-btn" id="fent" type="submit" value="Entrar">
                    </div>
                </form>
            </div>

            <!-- Formulário de Cadastro -->
            <div class="cadastro-content d-none" id="cad-form">
                <h2 class="login-title">Faça seu cadastro</h2>
                <form id="fcadastro" class="main-form" action="index.php" method="POST">
                    <input class="fcampo" name="user" type="text" placeholder="Digite seu usuário..." required>
                    <input class="fcampo" id="fuser" name="login" type="text" placeholder="Digite seu usuário ou email..." required>
                    <input class="fcampo" name="pass" type="password" placeholder="Digite sua senha..." required>
                    <input class="fcampo" name="cpass" type="password" placeholder="Confirme sua senha..." required>
                    <div class="fbuttons">
                        <button class="fbotao main-btn" type="submit" onclick="return validarSenha()">Cadastrar</button>
                    </div>
                    <div class="text-center">
                        <button type="button" onclick="toggleForm('log')">Já possui cadastro? <strong>Faça seu login</strong></button>
                    </div>
                </form>
            </div>

            <!-- Recuperação de Senha -->
            <div class="recuperacao-content d-none" id="rec-form">
                <h2 class="login-title">Recupere sua senha</h2>
                <form id="fsenha" class="main-form" action="index.php" method="POST">
                    <input class="fcampo" name="email_tel" type="text" placeholder="Digite seu email ou telefone..." required>
                    <div class="fbuttons">
                        <input class="fbotao main-btn" type="button" value="Enviar" onclick="enviarRecuperacao()">
                    </div>
                    <div class="text-center">
                        <button type="button" onclick="toggleForm('cad')">Não possui cadastro? <strong>Cadastre-se</strong></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tela de confirmação -->
        <div class="login-box confirma-box d-none" id="confirma-box-id"> 
            <div class="tela-confirma" id="t-confirmacao">
                <h2>Solicitação de redefinição de senha enviada!</h2>
                <p>Em alguns minutos, chegará em seu email/telefone uma senha temporária para acessar o sistema.</p>
            </div>
        </div>
    </main>

    <script src="js/script.js"></script>
    <script src="js/jquery.js"></script>
    <!-- <script src="js/sha512.js"></script> -->
    <script src="https://www.google.com/recaptcha/api.js?render=6LfiANwmAAAAABVkn1-V6qSE4O4kK45eKu72qqu7"></script>
    <!-- <script>
        function formhash(form, password) {
            var p = document.createElement("input");
            form.appendChild(p);
            p.name = "p";
            p.type = "hidden";
            p.value = hex_sha512(password.value);
            password.value = "";
            form.submit();
        }

        $(document).ready(function () {
            $("#flogin").on("submit", function (e) {
                e.preventDefault();
                formhash(this, document.getElementById("fpass"));
            });
        });
    </script> -->
    <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('6LfiANwmAAAAABVkn1-V6qSE4O4kK45eKu72qqu7', {action: 'submit'}).then(function(token) {
                document.getElementById('g-recaptcha-response').value = token;
            });
        });
    </script>
</div>

<?php include 'include/footer.php'; ?>
</body>
</html>
