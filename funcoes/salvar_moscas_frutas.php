<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'err' => 'method_not_allowed']);
    exit;
}

try {
    session_start();
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) throw new Exception("Usuário não autenticado");

    // Propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();
    if (!$prop) throw new Exception("Nenhuma propriedade ativa encontrada");
    $propriedade_id = $prop['id'];

    // Dados
    $data           = $_POST['data'] ?? null;
    $areas          = $_POST['area'] ?? [];
    $produtos       = $_POST['produto'] ?? [];
    $armadilha      = $_POST['armadilha'] ?? null;
    $atrativo       = $_POST['atrativo'] ?? null;
    $qtd_armadilhas = $_POST['qtd_armadilhas'] ?? null;
    $qtd_moscas     = $_POST['qtd_moscas'] ?? null;
    $obs            = $_POST['obs'] ?? null;

    if (!is_array($areas)) $areas = [$areas];
    if (!is_array($produtos)) $produtos = [$produtos];

    if (!$data || empty($areas) || empty($produtos) || !$armadilha || !$atrativo || !$qtd_armadilhas || !$qtd_moscas) {
        throw new Exception("Campos obrigatórios ausentes");
    }

    $mysqli->begin_transaction();

    // === Inserção principal ===
    // Se moscas foram capturadas, define status como 'concluido'
    $status = (!empty($qtd_moscas) && $qtd_moscas > 0) ? 'concluido' : 'pendente';

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'moscas_frutas', ?, ?, ?, ?)
    ");
    $stmt->bind_param("issss", $propriedade_id, $data, $qtd_moscas, $obs, $status);


    // === Detalhes ===
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");

    foreach ($areas as $a) {
        $stmt->bind_param("iss", $apontamento_id, $campo = "area_id", $valor = (string)$a);
        $stmt->execute();
    }

    foreach ($produtos as $p) {
        $stmt->bind_param("iss", $apontamento_id, $campo = "produto", $valor = (string)$p);
        $stmt->execute();
    }

    $detalhes = [
        'armadilha' => $armadilha,
        'atrativo' => $atrativo,
        'qtd_armadilhas' => $qtd_armadilhas
    ];

    foreach ($detalhes as $campo => $valor) {
        $stmt->bind_param("iss", $apontamento_id, $campo, (string)$valor);
        $stmt->execute();
    }

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Apontamento de Moscas das Frutas salvo com sucesso!']);

} catch (Exception $e) {
    if (isset($mysqli)) $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'exception', 'msg' => $e->getMessage()]);
}
