<?php
require_once __DIR__ . '/https.php';
require_once __DIR__ . '/secrets_loader.php';

// Conexão única, independente de HTTP_HOST
// Credenciais vêm de variáveis de ambiente ou de configuracao/secrets.php

$DB_HOST = getenv('DB_HOST') ?: 'caderno-db';
$DB_USER = getenv('DB_USER') ?: 'caderno_app';
$DB_PASS = caderno_secret('DB_PASSWORD') ?? caderno_secret('DB_PASS', '');
$DB_NAME = getenv('DB_NAME') ?: 'caderno';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('[caderno] Falha ao conectar no banco: ' . $e->getMessage());
    http_response_code(500);
    echo "<h3>Falha ao conectar no banco</h3>";
    echo "<p>Tente novamente em instantes. Se o problema persistir, contate o suporte.</p>";
    exit;
}

// Compatibilidade com código antigo
$cnx = $mysqli;
$conexao = $mysqli;
$db = $mysqli;

/**
 * Mensagem de erro segura para o cliente.
 * Erros de banco/infra são logados e substituídos por mensagem genérica;
 * exceções da aplicação (validações) mantêm a mensagem original.
 */
if (!function_exists('caderno_erro_msg')) {
    function caderno_erro_msg(Throwable $e, string $generica = 'Erro interno. Tente novamente mais tarde.'): string
    {
        if ($e instanceof mysqli_sql_exception || $e instanceof PDOException || $e instanceof Error) {
            error_log('[caderno] ' . get_class($e) . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
            return $generica;
        }
        return $e->getMessage();
    }
}

setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
