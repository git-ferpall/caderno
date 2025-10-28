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
    $area_id       = $_POST['area_id'] ?? null; // 🆕 vincula à área (bancada)
    $produto_id    = $_POST['produto_id'] ?? null;
    $dose          = trim($_POST['dose'] ?? '');
    $tipo          = trim($_POST['tipo'] ?? ''); // Foliar / Solução
    $obs           = trim($_POST['obs'] ?? '');
    $data          = date('Y-m-d'); // data atual

    if (!$estufa_id || !$area_id || !$produto_id) {
        throw new Exception('Campos obrigatórios ausentes (estufa, área ou produto).');
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
    $stmt = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, 'estufa_id', ?)
    ");
    $valor = (string)$estufa_id;
    $stmt->bind_param("is", $apontamento_id, $valor);
    $stmt->execute();
    $stmt->close();

    // 3️⃣ Inserir detalhes: area_id (referência da bancada)
    $stmt = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, 'area_id', ?)
    ");
    $valor = (string)$area_id;
    $stmt->bind_param("is", $apontamento_id, $valor);
    $stmt->execute();
    $stmt->close();

    // 4️⃣ Inserir detalhes: produto_id
    $stmt = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, 'produto_id', ?)
    ");
    $valor = (string)$produto_id;
    $stmt->bind_param("is", $apontamento_id, $valor);
    $stmt->execute();
    $stmt->close();

    // 5️⃣ Inserir detalhes: tipo de aplicação
    $stmt = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, 'tipo_aplicacao', ?)
    ");
    $valor = ($tipo == 1) ? "Foliar" : "Solução";
    $stmt->bind_param("is", $apontamento_id, $valor);
    $stmt->execute();
    $stmt->close();

    // ✅ Finaliza a transação
    $mysqli->commit();

    echo json_encode([
        'ok' => true,
        'msg' => '✅ Fertilizante aplicado com sucesso!'
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode([
        'ok' => false,
        'err' => $e->getMessage()
    ]);
}
