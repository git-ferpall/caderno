<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

try {
    // === Autenticação ===
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) {
        throw new Exception('Usuário não autenticado');
    }

    // === Propriedade ativa ===
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $prop = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$prop) throw new Exception('Nenhuma propriedade ativa encontrada');
    $propriedade_id = $prop['id'];

    // === Dados recebidos ===
    $estufa_id      = $_POST['estufa_id'] ?? null;
    $bancada_nome   = $_POST['bancada_nome'] ?? null;
    $produto_id     = $_POST['produto_id'] ?? null;
    $produto_outro  = trim($_POST['produto_outro'] ?? '');
    $dose           = trim($_POST['dose'] ?? '');
    $motivo         = trim($_POST['motivo'] ?? '');
    $obs            = trim($_POST['obs'] ?? '');
    $data           = date('Y-m-d');

    error_log("🔍 salvar_defensivo_hidroponia.php → estufa_id=$estufa_id, bancada_nome=$bancada_nome, produto_id=$produto_id, produto_outro=$produto_outro");

    if (!$estufa_id || !$bancada_nome) {
        throw new Exception("Dados obrigatórios ausentes (estufa ou bancada)");
    }

    // === Busca área_id e produto_id pela bancada ===
    $stmt = $mysqli->prepare("
        SELECT area_id, produto_id
        FROM bancadas
        WHERE estufa_id = ?
          AND LOWER(REPLACE(nome, ' ', '')) = LOWER(REPLACE(?, ' ', ''))
        LIMIT 1
    ");
    $stmt->bind_param("is", $estufa_id, $bancada_nome);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) throw new Exception("Bancada não encontrada ou sem vínculos");
    $area_id = $res['area_id'];
    $produto_bancada_id = $res['produto_id'];

    // === Inicia transação ===
    $mysqli->begin_transaction();

    // === Cria apontamento principal ===
    $tipo_apontamento = "defensivo";
    $status = "pendente";
    $quantidade = ($dose !== '') ? floatval($dose) : 0.0;

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdss", $propriedade_id, $tipo_apontamento, $data, $quantidade, $obs, $status);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();
    if (!$apontamento_id) throw new Exception("Falha ao criar apontamento principal");

    // === Detalhes: área e produto ===
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, 'area_id', ?)");
    $stmt->bind_param("is", $apontamento_id, $area_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, 'produto_id', ?)");
    $stmt->bind_param("is", $apontamento_id, $produto_bancada_id);
    $stmt->execute();
    $stmt->close();

    // === Defensivo aplicado ===
    $nome_defensivo = ($produto_id === 'outro' && $produto_outro !== '') ? $produto_outro : null;

    if ($nome_defensivo) {
        $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, 'defensivo', ?)");
        $stmt->bind_param("is", $apontamento_id, $nome_defensivo);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $mysqli->prepare("
            SELECT nome FROM inseticidas WHERE id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $produto_id);
        $stmt->execute();
        $nome = $stmt->get_result()->fetch_assoc()['nome'] ?? null;
        $stmt->close();

        if ($nome) {
            $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, 'defensivo', ?)");
            $stmt->bind_param("is", $apontamento_id, $nome);
            $stmt->execute();
            $stmt->close();
        }
    }

    // === Motivo ===
    $motivo_txt = ($motivo == 1) ? "Prevenção" : "Controle";
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, 'motivo', ?)");
    $stmt->bind_param("is", $apontamento_id, $motivo_txt);
    $stmt->execute();
    $stmt->close();

    // === Finaliza transação ===
    $mysqli->commit();
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    if ($mysqli->errno) $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
    error_log("❌ Erro salvar_defensivo_hidroponia.php → " . $e->getMessage());
}
