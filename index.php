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
<style>
           /* POPUP CENTRAL */
        .alert-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        backdrop-filter: blur(2px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        animation: fadeIn 0.4s ease;
        }

        .alert-login {
        background: #fff;
        border: 2px solid #ffb3b3;
        color: #a70000;
        padding: 25px 30px;
        border-radius: 12px;
        font-size: 16px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        max-width: 420px;
        width: 90%;
        text-align: center;
        animation: popIn 0.3s ease;
        position: relative;
        }

        .alert-login .alert-icon {
        font-size: 40px;
        margin-bottom: 10px;
        }

        .alert-login .alert-text {
        margin-bottom: 10px;
        line-height: 1.5;
        }

        .alert-login .alert-text strong {
        font-weight: 700;
        }

        .alert-login .alert-close {
        position: absolute;
        top: 8px;
        right: 12px;
        background: transparent;
        border: none;
        font-size: 22px;
        color: #a70000;
        cursor: pointer;
        }

        @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
        }

        @keyframes popIn {
        from { transform: scale(0.9); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
        }
        

</style>
</head>
<body>

<?php require 'include/loading.php'; ?>

<div id="conteudo" class="content-login">
    <header class="header-login" id="cabecalho">
        <div class="logo-box">
            <a href="/"><img src="img/logo-color.png" alt="Logo Caderno de Campo Frutag"></a>
            <a href="https://www.frutag.com.br" target="_blank"><img src="img/logo-frutag.png" alt="Logo Frutag"></a>
        </div>

        <?php 
        // 🔹 Exibe mensagens de erro vindas da sessão
        if (isset($_SESSION['retorno'])): 
            $msg = htmlspecialchars($_SESSION['retorno']['mensagem']);
        ?>
            <div class="alert-login" id="alert-login">
                <div class="alert-icon">⚠️</div>
                <div class="alert-text">
                    <strong>Erro ao entrar:</strong> <?= $msg ?>
                </div>
                <button class="alert-close" onclick="closeAlert()">×</button>
            </div>

            <?php unset($_SESSION['retorno']); ?>
        <?php endif; ?>

        <?php 
        // 🔹 Exibe mensagens de erro vindas da sessão
        if (isset($_SESSION['retorno'])): 
            $msg = htmlspecialchars($_SESSION['retorno']['mensagem']);
        ?>
        <div class="alert-overlay" id="alert-overlay">
        <div class="alert-login" id="alert-login">
            <button class="alert-close" onclick="closeAlert()">×</button>
            <div class="alert-icon">⚠️</div>
            <div class="alert-text">
                <strong>Erro ao entrar:</strong><br><?= $msg ?>
            </div>
        </div>
        </div>
        <?php unset($_SESSION['retorno']); ?>
        <?php endif; ?>
    </header>

    <main id="login" class="login">
        <div class="login-box" id="login-box-id">
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
                        <p class="offline-enter-hint">Disponível neste aparelho após login com internet.</p>
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

    // 🕒 Oculta automaticamente a mensagem de alerta após 7 segundos
    setTimeout(() => {
      const alertBox = document.querySelector('.alert-login');
      if (alertBox) {
        alertBox.style.transition = 'opacity 0.5s ease';
        alertBox.style.opacity = '0';
        setTimeout(() => alertBox.remove(), 500);
      }
    }, 7000);
    </script>
    <script>
    // Função para fechar manualmente o alerta
    function closeAlert() {
    const alert = document.getElementById('alert-login');
    if (alert) {
        alert.style.transition = 'opacity 0.3s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    }
    }

    // Remove automaticamente após 7 segundos
    setTimeout(closeAlert, 7000);
    </script>

    <script src="js/offline/db.js"></script>
    <script src="js/offline/session.js"></script>
    <script src="js/offline/login.js"></script>


</div>

<?php include 'include/footer.php'; ?>
</body>
</html>
