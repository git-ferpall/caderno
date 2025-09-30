<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;

    if (!$user_id) {
        throw new Exception("Usuário não autenticado");
    }

    // Pega a propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) {
        throw new Exception("Nenhuma propriedade ativa encontrada");
    }

    $propriedade_id = $prop['id'];
    $data       = $_POST['data'] ?? null;
    $area_id    = $_POST['area'] ?? null;
    $produto_id = $_POST['produto'] ?? null;
    $quantidade = $_POST['quantidade'] ?? null;
    $obs        = $_POST['obs'] ?? null;

    if (!$data || !$area_id || !$produto_id || !$quantidade) {
        throw new Exception("Campos obrigatórios não preenchidos");
    }

    // Salva na tabela apontamentos
    $tipo   = "colheita";
    $status = "pendente";

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdss", $propriedade_id, $tipo, $data, $quantidade, $obs, $status);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // Salva detalhes (área e produto)
    $detalhes = [
        ["campo" => "area_id", "valor" => $area_id],
        ["campo" => "produto_id", "valor" => $produto_id]
    ];

    foreach ($detalhes as $d) {
        $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $apontamento_id, $d['campo'], $d['valor']);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(["ok" => true, "msg" => "Colheita salva com sucesso!"]);
} catch (Throwable $e) {
    echo json_encode(["ok" => false, "erro" => $e->getMessage()]);
}
