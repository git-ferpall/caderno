<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json');

$payload = verify_jwt();
$user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

if (!$user_id) { echo json_encode([]); exit; }

$stmt = $mysqli->prepare("
    SELECT id, nome_arquivo, tipo_arquivo, tamanho_bytes, criado_em, origem 
    FROM silo_arquivos 
    WHERE user_id = ? 
    ORDER BY criado_em DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$dados = [];
while ($row = $res->fetch_assoc()) $dados[] = $row;

echo json_encode($dados);
