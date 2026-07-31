<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

// Pega user_id via sessão ou JWT
$user_id = caderno_require_user_id();
$produtos = [];
if ($user_id) {
    $stmt = $mysqli->prepare("SELECT * FROM produtos WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $produtos = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
