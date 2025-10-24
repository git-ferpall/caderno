<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

// 🔐 Identifica usuário logado
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// 🏠 Propriedade ativa
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

// 🧾 Dados recebidos
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

// 🚀 Transação segura
$mysqli->begin_transaction();

try {
    // 🏗️ 1️⃣ Salva bancada
    $stmt = $mysqli->prepare("
        INSERT INTO bancadas (estufa_id, nome, cultura, obs)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $estufa_id, $nome, $cultura, $obs);
    $stmt->execute();
    $bancada_id = $stmt->insert_id;
    $stmt->close();

    if ($bancada_id <= 0) {
        throw new Exception('Erro ao salvar na tabela bancadas.');
    }

    // 🔍 2️⃣ Busca nome da estufa
    $stmt2 = $mysqli->prepare("SELECT nome FROM estufas WHERE id = ?");
    $stmt2->bind_param("i", $estufa_id);
    $stmt2->execute();
    $r = $stmt2->get_result();
    $estufa = $r->fetch_assoc();
    $stmt2->close();

    $nome_estufa = $estufa ? $estufa['nome'] : 'Estufa sem nome';

    // 🌱 3️⃣ Cria também em áreas
    // Verifica se enum já possui 'bancada'
    $tipo = 'bancada'; // se enum ainda não tiver, use 'estufa'

    $nome_area = "{$nome_estufa} - Bancada {$nome}";
    $stmt3 = $mysqli->prepare("
        INSERT INTO areas (user_id, propriedade_id, nome, tipo)
        VALUES (?, ?, ?, ?)
    ");
    $stmt3->bind_param("iiss", $user_id, $propriedade_id, $nome_area, $tipo);
    $stmt3->execute();
    $area_id = $stmt3->insert_id;
    $stmt3->close();

    if ($area_id <= 0) {
        throw new Exception('Erro ao salvar na tabela areas.');
    }

    // ✅ Confirma tudo
    $mysqli->commit();

    echo json_encode([
        'ok' => true,
        'msg' => 'Bancada salva com sucesso!',
        'bancada_id' => $bancada_id,
        'area_id' => $area_id
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
