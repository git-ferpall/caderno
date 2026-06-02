<?php
declare(strict_types=1);

/**
 * Retorna URL do QR da bancada (valida propriedade do usuário).
 */
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/hidroponia_helpers.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    try {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'err' => 'Não autenticado.']);
        exit;
    }
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado.']);
    exit;
}

$bancada_id = isset($_GET['bancada_id']) ? (int) $_GET['bancada_id'] : 0;
if ($bancada_id <= 0) {
    echo json_encode(['ok' => false, 'err' => 'Bancada inválida.']);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT b.id, b.nome, b.estufa_id, e.nome AS estufa_nome
    FROM bancadas b
    INNER JOIN estufas e ON e.id = b.estufa_id
    INNER JOIN propriedades p ON p.id = e.propriedade_id AND p.user_id = ? AND p.ativo = 1
    WHERE b.id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $user_id, $bancada_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['ok' => false, 'err' => 'Bancada não encontrada ou sem permissão.']);
    exit;
}

$url = hidroponiaUrlBancada($bancada_id);

echo json_encode([
    'ok' => true,
    'bancada_id' => $bancada_id,
    'bancada_nome' => $row['nome'],
    'estufa_id' => (int) $row['estufa_id'],
    'estufa_nome' => $row['estufa_nome'],
    'url' => $url,
], JSON_UNESCAPED_UNICODE);
