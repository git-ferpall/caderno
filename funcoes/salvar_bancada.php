<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

// 🔐 Identifica o usuário logado
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// 🏠 Verifica propriedade ativa (para garantir consistência)
$stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$prop = $res->fetch_assoc();
$stmt->close();

if (!$prop) {
    echo json_encode(['ok' => false, 'err' => 'Nenhuma propriedade ativa encontrada']);
    exit;
}

// 📦 Dados do formulário
$estufa_id = (int)($_POST['estufa_id'] ?? 0);
$nome      = trim($_POST['nome'] ?? '');
$cultura   = trim($_POST['cultura'] ?? '');
$obs       = trim($_POST['obs'] ?? '');

if ($estufa_id <= 0) {
    echo json_encode(['ok' => false, 'err' => 'Estufa não identificada']);
    exit;
}
if ($nome === '') {
    echo json_encode(['ok' => false, 'err' => 'O nome da bancada é obrigatório']);
    exit;
}

// 💾 Insere no banco
$stmt = $mysqli->prepare("
    INSERT INTO bancadas (estufa_id, nome, cultura, obs)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("isss", $estufa_id, $nome, $cultura, $obs);
$ok = $stmt->execute();
$new_id = $stmt->insert_id;
$stmt->close();

if ($ok) {
    echo json_encode(['ok' => true, 'id' => $new_id]);
} else {
    echo json_encode(['ok' => false, 'err' => 'Falha ao salvar a bancada.']);
}
