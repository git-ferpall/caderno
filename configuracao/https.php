<?php
declare(strict_types=1);

/**
 * Garante que Apache/PHP reconheçam HTTPS atrás de proxy reverso (nginx).
 * Incluir cedo em index.php, protect.php ou configuracao_conexao.php.
 */
$forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
$forwardedPort = (string) ($_SERVER['HTTP_X_FORWARDED_PORT'] ?? '');
$isHttps =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || $forwardedProto === 'https'
    || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443')
    || $forwardedPort === '443';

if ($isHttps) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '443';
    $_SERVER['REQUEST_SCHEME'] = 'https';
}

/** Redirecionamento relativo (mantém HTTPS no navegador). */
function caderno_redirect(string $path, int $code = 302): never
{
    if ($path !== '' && $path[0] !== '/') {
        $path = '/' . $path;
    }
    header('Location: ' . $path, true, $code);
    exit;
}
