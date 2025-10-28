<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

try {
    // === Identifica o usuário logado ===
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }

    if (!$user_id) {
        throw new Exception('Usuário não autenticado');
    }

    // === Descobre propriedade ativa ===
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) {
        throw new Exception('Nenhuma propriedade ativa encontrada');
    }

    $propriedade_id = $prop['id'];

    // === Dados vindos do formulário (via fetch POST) ===
    $estufa_id     = $_POST['estufa_id'] ?? null;
    $bancada_nome  = $_POST['bancada_nome'] ?? null;
    $produto       = trim($_POST['nome'] ?? '');
    $dose          = trim($_POST['dose'] ?? '');
    $tipo          = trim($_POST['tipo'] ?? ''); // Foliar / Solução
    $obs           = trim($_POST['obs'] ?? '');
    $data          = date('Y-m-d'); // data atual

    if (!$estufa_id || !$bancada_nome || $produto === '') {
        throw new Exception('Preencha todos os campos obrigatórios.');
    }

    // === Transação para consistência ===
    $mysqli->begin_transaction();

    // 1️⃣ Inserir o apontamento principal
    $tipo_apontamento = "fertilizante";
    $status = "pendente";

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $quantidade = ($dose !== '') ? floatval($dose) : 0;
    $stmt->bind_param("issdss", $propriedade_id, $tipo_apontamento, $data, $quantidade, $obs, $status);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // 2️⃣ Inserir detalhes: estufa_id
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    $campo = "estufa_id";
    $valor = (string)$estufa_id;
    $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmt->execute();
    $stmt->close();

    // 3️⃣ Inserir detalhes: bancada_nome
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    $campo = "bancada_nome";
    $valor = $bancada_nome;
    $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmt->execute();
    $stmt->close();

    // 4️⃣ Inserir detalhes: produto (fertilizante)
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    $campo = "fertilizante";
    $valor = $produto;
    $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmt->execute();
    $stmt->close();

    // 5️⃣ Inserir detalhes: tipo de aplicação
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    $campo = "tipo_aplicacao";
    $valor = ($tipo == 1) ? "Foliar" : "Solução";
    $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmt->execute();
    $stmt->close();

    // 6️⃣ Inserir observação (se houver)
    if ($obs !== '') {
        $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
        $campo = "observacoes";
        $valor = $obs;
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();
        $stmt->close();
    }

    $mysqli->commit();

    echo json_encode([
        'ok' => true,
        'msg' => '✅ Fertilizante aplicado com sucesso (Hidroponia)!'
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode([
        'ok' => false,
        'err' => $e->getMessage()
    ]);
}
