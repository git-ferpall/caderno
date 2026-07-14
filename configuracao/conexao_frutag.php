<?php
// configuracao/conexao_frutag.php
require_once __DIR__ . '/secrets_loader.php';

$FRUTAG_DB_HOST = caderno_secret('FRUTAG_DB_HOST', '');
$FRUTAG_DB_USER = caderno_secret('FRUTAG_DB_USER', '');
$FRUTAG_DB_PASS = caderno_secret('FRUTAG_DB_PASS', '');
$FRUTAG_DB_NAME = caderno_secret('FRUTAG_DB_NAME', '');

try {
    $pdo_frutag = new PDO(
        "mysql:host={$FRUTAG_DB_HOST};port=3306;dbname={$FRUTAG_DB_NAME};charset=utf8mb4",
        $FRUTAG_DB_USER,
        $FRUTAG_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    error_log('[caderno] Falha ao conectar no banco Frutag: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'err' => 'db_frutag',
        'msg' => 'Erro interno de conexão. Tente novamente mais tarde.'
    ]);
    exit;
}
