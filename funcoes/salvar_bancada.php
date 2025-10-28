<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

// 🧾 Grava debug local (para entender o que chega do JS)
file_put_contents(__DIR__ . "/debug_bancada.txt", print_r($_POST, true) . "\n---\n", FILE_APPEND);

// 🔐 Identifica usuário logado
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    try {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'err' => 'Falha ao validar token.']);
        exit;
    }
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// 🏠 Busca propriedade ativa
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

// 🧾 Dados recebidos do formulário
$estufa_id  = isset($_POST['estufa_id']) ? (int)$_POST['estufa_id'] : 0;
$nome       = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$produto_id = isset($_POST['produto_id']) ? (int)$_POST['produto_id'] : 0;
$obs        = isset($_POST['obs']) ? trim($_POST['obs']) : '';

if ($estufa_id <= 0) {
    echo json_encode(['ok' => false, 'err' => 'Estufa não identificada']);
    exit;
}
if ($nome === '') {
    echo json_encode(['ok' => false, 'err' => 'O nome da bancada é obrigatório']);
    exit;
}
if ($produto_id <= 0) {
    echo json_encode(['ok' => false, 'err' => 'Selecione o produto (cultura) da bancada']);
    exit;
}

try {
    $mysqli->begin_transaction();

    // 🔍 Busca nome da estufa (para compor o nome da área)
    $stmt2 = $mysqli->prepare("SELECT nome FROM estufas WHERE id = ?");
    $stmt2->bind_param("i", $estufa_id);
    $stmt2->execute();
    $r = $stmt2->get_result();
    $estufa = $r->fetch_assoc();
    $stmt2->close();

    $nome_estufa = $estufa ? $estufa['nome'] : 'Estufa sem nome';
    $nome_area = "{$nome_estufa} - Bancada {$nome}";
    $tipo_area = 'bancada';

    // 🌱 1️⃣ Cria uma nova área vinculada
    $stmt3 = $mysqli->prepare("
        INSERT INTO areas (user_id, propriedade_id, nome, tipo)
        VALUES (?, ?, ?, ?)
    ");
    $stmt3->bind_param("iiss", $user_id, $propriedade_id, $nome_area, $tipo_area);
    $stmt3->execute();
    $area_id = $stmt3->insert_id;
    $stmt3->close();

    if ($area_id <= 0) {
        throw new Exception('Erro ao salvar a área vinculada.');
    }

    // 🧱 2️⃣ Cria a bancada vinculada à área e ao produto selecionado
    $stmt = $mysqli->prepare("
        INSERT INTO bancadas (area_id, estufa_id, nome, produto_id, obs)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiiss", $area_id, $estufa_id, $nome, $produto_id, $obs);
    $stmt->execute();
    $bancada_id = $stmt->insert_id;
    $stmt->close();

    if ($bancada_id <= 0) {
        throw new Exception('Erro ao salvar a bancada.');
    }

    // ✅ Confirma transação
    $mysqli->commit();

    echo json_encode([
        'ok' => true,
        'msg' => '✅ Bancada salva com sucesso!',
        'bancada_id' => $bancada_id,
        'area_id' => $area_id,
        'debug' => [
            'estufa_id' => $estufa_id,
            'nome' => $nome,
            'produto_id' => $produto_id,
            'obs' => $obs
        ]
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
