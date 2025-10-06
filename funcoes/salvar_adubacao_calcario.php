<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

// Identifica usuário logado
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// Busca propriedade ativa
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
$propriedade_id = $prop['id'];

// Dados do formulário
$data            = $_POST['data'] ?? null;
$areas           = $_POST['area'] ?? [];
$produto         = $_POST['produto'] ?? null;
$tipo_produto    = $_POST['tipo'] ?? null;
$quantidade      = $_POST['quantidade'] ?? null;
$prnt            = $_POST['prnt'] ?? null;
$forma_aplicacao = $_POST['forma_aplicacao'] ?? null;
$n_referencia    = $_POST['n_referencia'] ?? null;
$obs             = $_POST['obs'] ?? null;

// Validação básica
if (!$data || empty($areas) || !$produto || !$tipo_produto || !$quantidade) {
    echo json_encode(['ok' => false, 'err' => 'Preencha todos os campos obrigatórios']);
    exit;
}

try {
    $mysqli->begin_transaction();

    // Inserir registro principal
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'adubacao_calcario', ?, ?, ?, 'pendente')
    ");
    $stmt->bind_param("isss", $propriedade_id, $data, $quantidade, $obs);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // Inserir detalhes
    $stmt = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, ?, ?)
    ");

    // Áreas
    foreach ($areas as $area_id) {
        $stmt->bind_param("iss", $apontamento_id, $campo = 'area_id', $valor = (string)(int)$area_id);
        $stmt->execute();
    }

    // Demais campos
    $detalhes = [
        'produto'         => $produto,
        'tipo_produto'    => $tipo_produto,
        'prnt'            => $prnt,
        'forma_aplicacao' => $forma_aplicacao,
        'n_referencia'    => $n_referencia,
    ];

    foreach ($detalhes as $campo => $valor) {
        if ($valor !== null && $valor !== '') {
            $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
            $stmt->execute();
        }
    }

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Adubação registrada com sucesso!']);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => 'Erro: ' . $e->getMessage()]);
}
