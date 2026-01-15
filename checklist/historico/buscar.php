<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

$q = trim($_GET['q'] ?? '');
if ($q === '' || strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT
        c.id,
        c.titulo,
        c.concluido
    FROM checklists c
    WHERE c.user_id = ?
      AND c.titulo LIKE ?
    ORDER BY c.criado_em DESC
    LIMIT 10
");
$like = "%$q%";
$stmt->bind_param("is", $user_id, $like);
$stmt->execute();

$res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($res, JSON_UNESCAPED_UNICODE);
