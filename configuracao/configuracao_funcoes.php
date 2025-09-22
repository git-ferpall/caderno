<?php
function sec_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
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

function isLogged() {
    return !empty($_SESSION['auth']);
}

function verificaSessaoExpirada($tempo_inatividade = 1800) {
    if (isset($_SESSION['ultimo_acesso'])) {
        $tempo_sessao = time() - $_SESSION['ultimo_acesso'];
        if ($tempo_sessao > $tempo_inatividade) {
            session_unset();
            session_destroy();
            header("Location: /login.php?msg=expirada");
            exit();
        }
    }
    $_SESSION['ultimo_acesso'] = time();
}
