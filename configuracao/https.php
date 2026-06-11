<?php
declare(strict_types=1);

/**
 * Garante que Apache/PHP reconheçam HTTPS atrás de proxy reverso (nginx).
 * Incluir cedo em index.php, protect.php ou configuracao_conexao.php.
 */
if (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on')
) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '443';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $_SERVER['REQUEST_SCHEME'] = 'https';
    }
}
