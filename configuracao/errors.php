<?php
declare(strict_types=1);

/**
 * Configuração segura de erros PHP.
 * Em produção: erros vão para o log, não para a resposta HTTP.
 * Para depuração local: defina CADERNO_DEBUG=1 no ambiente.
 */
function caderno_is_debug(): bool
{
    $flag = getenv('CADERNO_DEBUG');
    if ($flag !== false && in_array(strtolower((string) $flag), ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }

    return false;
}

function caderno_configure_errors(): void
{
    if (caderno_is_debug()) {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        return;
    }

    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

caderno_configure_errors();
