<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/hidroponia_helpers.php';
header('Content-Type: application/json; charset=utf-8');
session_start();
// 🧾 Grava debug local (para entender o que chega do JS)
//file_put_contents(__DIR__ . "/debug_bancada.txt", print_r($_POST, true) . "\n---\n", FILE_APPEND);

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
$obs        = isset($_POST['obs']) ? trim($_POST['obs']) : '';
$barea = isset($_POST['barea']) ? (float)$_POST['barea'] : 0;
$barea_unidade = isset($_POST['barea_unidade']) ? $_POST['barea_unidade'] : 'm2';

$produto_ids = [];
$produtos_json = isset($_POST['produtos_json']) ? trim((string) $_POST['produtos_json']) : '';
$produtos_detalhe = [];

if ($produtos_json !== '') {
    $decoded = json_decode($produtos_json, true);
    if (is_array($decoded)) {
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $pid = (int) ($item['produto_id'] ?? $item['id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $produtos_detalhe[] = [
                'produto_id' => $pid,
                'area_m2' => isset($item['area_m2']) ? (float) $item['area_m2'] : 0.0,
                'percentual' => isset($item['percentual']) ? (float) $item['percentual'] : 0.0,
            ];
            $produto_ids[] = $pid;
        }
    }
}

if (!$produto_ids) {
    if (isset($_POST['produto_id']) && is_array($_POST['produto_id'])) {
        $produto_ids = array_map('intval', $_POST['produto_id']);
    } elseif (isset($_POST['produto_id'])) {
        $produto_ids = [(int) $_POST['produto_id']];
    }
}
$produto_ids = array_values(array_unique(array_filter($produto_ids, static fn ($id) => $id > 0)));
$produto_id = $produto_ids[0] ?? 0;

// converte hectares para m²
if ($barea_unidade === 'ha') {
    $barea = $barea * 10000;
}


if ($estufa_id <= 0) {
    echo json_encode(['ok' => false, 'err' => 'Estufa não identificada']);
    exit;
}
if ($nome === '') {
    echo json_encode(['ok' => false, 'err' => 'O nome da bancada é obrigatório']);
    exit;
}
if (!$produto_ids) {
    echo json_encode(['ok' => false, 'err' => 'Selecione ao menos um produto (cultura) da bancada']);
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
    INSERT INTO areas (user_id, propriedade_id, nome, tipo, tamanho)
    VALUES (?, ?, ?, ?, ?)
    ");

    $stmt3->bind_param(
        "iissd",
        $user_id,
        $propriedade_id,
        $nome_area,
        $tipo_area,
        $barea
    );
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
    $stmt->bind_param("iisis", $area_id, $estufa_id, $nome, $produto_id, $obs);
    $stmt->execute();
    $bancada_id = $stmt->insert_id;
    $stmt->close();

    if ($bancada_id <= 0) {
        throw new Exception('Erro ao salvar a bancada.');
    }

    hidroponiaSalvarProdutosBancadaDetalhe($mysqli, $bancada_id, $produtos_detalhe ?: array_map(
        static fn ($id) => ['produto_id' => $id],
        $produto_ids
    ), $barea);

    // ✅ Confirma transação
    $mysqli->commit();

    // Retorno padrão do sistema (sem mensagem visual)
    echo json_encode([
        'ok' => true,
        'bancada_id' => $bancada_id,
        'area_id' => $area_id
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => caderno_erro_msg($e)]);
}
