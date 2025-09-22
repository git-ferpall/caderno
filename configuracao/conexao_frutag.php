<?php
// conexao_frutag.php
// Conexão dedicada ao banco remoto fruta169_frutag (HostGator/cPanel)

$FRUTAG_HOST = 'localhost';       // geralmente "localhost" no cPanel
$FRUTAG_DB   = 'fruta169_frutag';
$FRUTAG_USER = 'fruta169_sso';    // usuário criado no cPanel
$FRUTAG_PASS = 'S3nh@SSO-MuitoForte!';  // senha definida no cPanel

try {
    $pdo_frutag = new PDO(
        "mysql:host={$FRUTAG_HOST};dbname={$FRUTAG_DB};charset=utf8mb4",
        $FRUTAG_USER,
        $FRUTAG_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'db_frutag', 'msg' => $e->getMessage()]);
    exit;
}
