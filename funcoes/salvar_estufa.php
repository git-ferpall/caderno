<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

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

// 🏠 Busca a propriedade ativa
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

$propriedade_id = (int)$prop['id'];

// 📦 Dados do formulário
$nome     = trim($_POST['nome'] ?? '');
$area_m2  = trim($_POST['area_m2'] ?? '');
$obs      = trim($_POST['obs'] ?? '');

if ($nome === '') {
    echo json_encode(['ok' => false, 'err' => 'O nome da estufa é obrigatório']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

// ✏️ Atualiza estufa existente
if ($id > 0) {
    $stmt = $mysqli->prepare("
        UPDATE estufas
        SET nome = ?, area_m2 = ?, obs = ?
        WHERE id = ? AND propriedade_id = ?
    ");
    $stmt->bind_param("sssii", $nome, $area_m2, $obs, $id, $propriedade_id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        echo json_encode(['ok' => false, 'err' => 'Falha ao atualizar a estufa.']);
        exit;
    }

    // Mantém o nome das áreas vinculadas às bancadas em sincronia
    $stmt = $mysqli->prepare("
        UPDATE areas a
        INNER JOIN bancadas b ON b.area_id = a.id
        SET a.nome = CONCAT(?, ' - Bancada ', b.nome)
        WHERE b.estufa_id = ?
    ");
    $stmt->bind_param("si", $nome, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'id' => $id]);
    exit;
}

// 💾 Insere no banco
$stmt = $mysqli->prepare("
    INSERT INTO estufas (propriedade_id, nome, area_m2, obs)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("isss", $propriedade_id, $nome, $area_m2, $obs);
$ok = $stmt->execute();
$new_id = $stmt->insert_id;
$stmt->close();

if ($ok) {
    echo json_encode(['ok' => true, 'id' => $new_id]);
} else {
    echo json_encode(['ok' => false, 'err' => 'Falha ao salvar a estufa.']);
}
