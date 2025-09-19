<?php
// Conexão única, independente de HTTP_HOST
// Lê DB_PASSWORD OU DB_PASS e usa defaults do banco local (caderno-db)

$DB_HOST = getenv('DB_HOST')      ?: 'caderno-db';
$DB_USER = getenv('DB_USER')      ?: 'caderno_app';
$DB_PASS = getenv('DB_PASSWORD')  ?: (getenv('DB_PASS') ?: 'C@dern0_App#2025!');
$DB_NAME = getenv('DB_NAME')      ?: 'caderno';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo "<h3>Falha ao conectar no banco</h3>";
    echo "<pre>Host: {$DB_HOST}\nUsuário: {$DB_USER}\nDB: {$DB_NAME}\nErro: {$e->getMessage()}</pre>";
    exit;
}

// Compatibilidade com código antigo
$cnx = $mysqli;
$conexao = $mysqli;
$db = $mysqli;

setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
