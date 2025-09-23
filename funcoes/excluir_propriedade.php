<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

session_start();

// garante user_id via token ou sessão
$payload = verify_jwt();
if ($payload && !empty($payload['sub'])) {
    $_SESSION['user_id'] = $payload['sub'];
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Usuário não logado");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("ID inválido");
}

// excluir somente se a propriedade pertence ao usuário
$stmt = $mysqli->prepare("DELETE FROM propriedades WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // sucesso → redireciona de volta
    header("Location: /home/minhas_propriedades.php?msg=excluido");
    exit;
} else {
    // não encontrou ou não pertence ao usuário
    header("Location: /home/minhas_propriedades.php?msg=erro");
    exit;
}
