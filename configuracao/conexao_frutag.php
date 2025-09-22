<?php
// configuracao/conexao_frutag.php

$FRUTAG_DB_HOST = '162.241.101.61';      // IP do HostGator
$FRUTAG_DB_USER = 'fruta169_sso';        // seu usuário MySQL remoto
$FRUTAG_DB_PASS = 'S3nh@SSO-MuitoForte!'; // senha
$FRUTAG_DB_NAME = 'fruta169_frutag';     // banco

try {
    $pdo_frutag = new PDO(
        "mysql:host={$FRUTAG_DB_HOST};port=3306;dbname={$FRUTAG_DB_NAME};charset=utf8mb4",
        $FRUTAG_DB_USER,
        $FRUTAG_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'err' => 'db_frutag',
        'msg' => $e->getMessage()
    ]);
    exit;
}
