<?php
session_start();

require_once __DIR__ . '/configuracao/auth.php';
require_once __DIR__ . '/configuracao/recaptcha.php'; 

if (function_exists('isLogged') ? isLogged() : (current_user() !== null)) {
    header('Location: /home');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo - Frutag</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <script>
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("/sw.js").catch(function () {});
    }
    </script>
</head>
<body class="page-login">

<?php require 'include/loading.php'; ?>

<div id="conteudo" class="content-login">
    <div class="login-page-shell">
    <header class="header-login" id="cabecalho">
        <div class="logo-box">
            <a href="/"><img src="img/logo-color.png" alt="Logo Caderno de Campo Frutag"></a>
            <a href="https://www.frutag.com.br" target="_blank" rel="noopener"><img src="img/logo-frutag.png" alt="Logo Frutag"></a>
        </div>
    </header>

    <?php if (isset($_SESSION['retorno'])):
        $msg = htmlspecialchars($_SESSION['retorno']['mensagem']);
        unset($_SESSION['retorno']);
    ?>
    <div class="alert-overlay" id="alert-overlay" role="alertdialog" aria-labelledby="alert-login-title">
        <div class="alert-login" id="alert-login">
            <button type="button" class="alert-close" onclick="closeAlert()" aria-label="Fechar">×</button>
            <div class="alert-icon" aria-hidden="true">⚠️</div>
            <div class="alert-text" id="alert-login-title">
                <strong>Erro ao entrar:</strong><br><?= $msg ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <main id="login" class="login">
        <div class="login-box" id="login-box-id">
            <div class="login-brand-mobile">
                <img src="img/logo-color.png" alt="Caderno de Campo Frutag" width="140" height="auto">
            </div>
            <!-- Formulário de Login -->
            <div class="login-content" id="login-form">
                <h2 class="login-title">Faça seu login</h2>
                <form id="flogin" class="main-form" action="/configuracao/login_process.php" method="POST">
                    <input class="fcampo" id="fuser" name="login" type="text" placeholder="Digite seu usuário ou email..." required autocomplete="username">
                    <input class="fcampo" id="fpass" name="senha" type="password" placeholder="Digite sua senha..." required autocomplete="current-password">
                    <input type="hidden" name="next" value="<?= htmlspecialchars($_GET['next'] ?? '/') ?>">
                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                    <div class="text-center">
                        <button id="fesq" name="esqueci" type="button" onclick="toggleForm('rec')">Esqueceu sua senha? <strong>Clique aqui</strong></button>
                    </div>
                    <div class="fbuttons">
                        <button class="fbotao main-btn" id="fcad" type="button" onclick="toggleForm('cad')">Cadastrar</button>
                        <input class="fbotao main-btn" id="fent" type="submit" value="Entrar">
                    </div>
                    <div id="offline-enter-wrap" class="offline-enter-wrap d-none">
                        <div class="offline-enter-divider">ou</div>
                        <button type="button" id="btn-offline-enter" class="fbotao main-btn fundo-azul offline-enter-btn">Continuar offline</button>
                        <p class="offline-enter-hint">Disponível neste aparelho após login com internet (offline ativo por padrão).</p>
                    </div>
                </form>
            </div>

            <!-- Formulário de Cadastro -->
            <div class="cadastro-content d-none" id="cad-form">
                <h2 class="login-title">Faça seu cadastro</h2>
                <form id="fcadastro" class="main-form" action="/" method="POST">
                    <input class="fcampo" name="user" type="text" placeholder="Digite seu usuário..." required>
                    <input class="fcampo" id="fuser_cad" name="login" type="text" placeholder="Digite seu usuário ou email..." required autocomplete="username">
                    <input class="fcampo" id="fpass_cad" name="pass" type="password" placeholder="Digite sua senha..." required autocomplete="new-password">
                    <input class="fcampo" id="fcpass_cad" name="cpass" type="password" placeholder="Confirme sua senha..." required autocomplete="new-password">
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
                <form id="fsenha" class="main-form" action="/" method="POST">
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
    </div>

    <script src="js/script.js"></script>
    <script src="js/jquery.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>"></script>

    <script>
    // ReCAPTCHA handler
    document.getElementById('flogin').addEventListener('submit', function(e) {
        e.preventDefault();
        grecaptcha.ready(function() {
            grecaptcha.execute('<?= RECAPTCHA_SITE_KEY ?>', {action: 'login'}).then(function(token) {
                document.getElementById('g-recaptcha-response').value = token;
                e.target.submit(); // envia só depois de ter o token válido
            });
        });
    });

    function closeAlert() {
      const overlay = document.getElementById('alert-overlay');
      if (!overlay) return;
      overlay.style.transition = 'opacity 0.3s ease';
      overlay.style.opacity = '0';
      setTimeout(() => overlay.remove(), 300);
    }
    setTimeout(closeAlert, 7000);
    </script>

    <script src="js/offline/db.js"></script>
    <script src="js/offline/session.js"></script>
    <script src="js/offline/login.js"></script>


</div>

<?php include 'include/footer.php'; ?>
</body>
</html>
