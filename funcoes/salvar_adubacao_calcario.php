<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'err' => 'method_not_allowed']);
    exit;
}

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'unauthorized']);
        exit;
    }

    // Dados básicos
    $data            = $_POST['data'] ?? null;
    $areas           = $_POST['area'] ?? [];
    $produto         = $_POST['produto'] ?? null;
    $tipo_produto    = $_POST['tipo'] ?? null;
    $quantidade      = $_POST['quantidade'] ?? null;
    $prnt            = $_POST['prnt'] ?? null;
    $forma_aplicacao = $_POST['forma_aplicacao'] ?? null;
    $n_referencia    = $_POST['n_referencia'] ?? null;
    $obs             = $_POST['obs'] ?? null;

    // Buscar propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) {
        echo json_encode(["ok" => false, "err" => "Nenhuma propriedade ativa encontrada"]);
        exit;
    }
    $propriedade_id = $prop['id'];

    // Criar apontamento principal
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, status)
        VALUES (?, 'adubacao_calcario', ?, 'pendente')
    ");
    $stmt->bind_param("is", $propriedade_id, $data);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // Inserir campos principais
    $dados = [
        ['chave' => 'produto',         'valor' => $produto],
        ['chave' => 'tipo_produto',    'valor' => $tipo_produto],
        ['chave' => 'quantidade',      'valor' => $quantidade],
        ['chave' => 'prnt',            'valor' => $prnt],
        ['chave' => 'forma_aplicacao', 'valor' => $forma_aplicacao],
        ['chave' => 'n_referencia',    'valor' => $n_referencia],
        ['chave' => 'obs',             'valor' => $obs],
    ];

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos_dados (apontamento_id, user_id, chave, valor)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($dados as $d) {
        if ($d['valor'] !== null && $d['valor'] !== '') {
            $stmt->bind_param("iiss", $apontamento_id, $user_id, $d['chave'], $d['valor']);
            $stmt->execute();
        }
    }
    $stmt->close();

    // Salvar as áreas selecionadas
    if (!empty($areas)) {
        $stmt = $mysqli->prepare("
            INSERT INTO apontamentos_dados (apontamento_id, user_id, chave, valor)
            VALUES (?, ?, 'area_id', ?)
        ");
        foreach ($areas as $area_id) {
            $stmt->bind_param("iis", $apontamento_id, $user_id, $area_id);
            $stmt->execute();
        }
        $stmt->close();
    }

    echo json_encode([
        "ok" => true,
        "msg" => "Adubação registrada com sucesso!",
        "id" => $apontamento_id
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'err' => 'exception',
        'msg' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
