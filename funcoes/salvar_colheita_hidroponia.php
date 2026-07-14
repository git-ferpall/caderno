<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/hidroponia_helpers.php';

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

    if (!$prop) {
        throw new Exception('Nenhuma propriedade ativa encontrada');
    }
    $propriedade_id = $prop['id'];

    // === Dados do formulário ===
    $estufa_id   = $_POST['estufa_id'] ?? null;
    $bancada_nome = $_POST['area_id'] ?? null; // vem do nome da bancada (ex: Bancada 01)
    $quantidade  = trim($_POST['quantidade'] ?? '');
    $destino     = trim($_POST['destino'] ?? '');
    $obs         = trim($_POST['obs'] ?? '');
    $data        = date('Y-m-d');

    if (!$bancada_nome || !$estufa_id) {
        throw new Exception("Campos obrigatórios não informados (bancada ou estufa)");
    }

    // === Busca área e produto da bancada ===
    $stmt = $mysqli->prepare("
        SELECT id AS bancada_id, area_id, produto_id 
        FROM bancadas 
        WHERE estufa_id = ? AND nome LIKE CONCAT('%', ?, '%') 
        LIMIT 1
    ");
    $stmt->bind_param("is", $estufa_id, $bancada_nome);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) {
        throw new Exception("Bancada não encontrada ou sem vínculos com área/produto.");
    }

    $area_id_real = $res['area_id'];
    $produto_id_real = (int) $res['produto_id'];

    $cultivo_produto_id = isset($_POST['cultivo_produto_id']) ? (int) $_POST['cultivo_produto_id'] : 0;
    if ($cultivo_produto_id > 0) {
        $produtos_bancada = hidroponiaListarProdutosBancada(
            $mysqli,
            (int) ($res['bancada_id'] ?? 0),
            $produto_id_real
        );
        $ids_ok = array_column($produtos_bancada, 'id');
        if (in_array($cultivo_produto_id, $ids_ok, true)) {
            $produto_id_real = $cultivo_produto_id;
        }
    }

    // === Traduz destino numérico para texto ===
    switch ($destino) {
        case "1": $destino_txt = "Comercialização"; break;
        case "2": $destino_txt = "Consumo"; break;
        case "3": $destino_txt = "Descarte"; break;
        default:  $destino_txt = "Não especificado"; break;
    }

    // === Inicia transação ===
    $mysqli->begin_transaction();

    // === Cria apontamento principal ===
    $tipo_apontamento = "colheita";
    $status = "pendente";
    $qtd = ($quantidade !== '') ? floatval($quantidade) : 0.0;

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdss",
        $propriedade_id,
        $tipo_apontamento,
        $data,
        $qtd,
        $obs,
        $status
    );
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    if (!$apontamento_id) {
        throw new Exception("Falha ao criar apontamento principal.");
    }

    // === Salva detalhes da colheita ===
    // Área vinculada à bancada
    $stmt = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) 
        VALUES (?, 'area_id', ?)
    ");
    $stmt->bind_param("is", $apontamento_id, $area_id_real);
    $stmt->execute();
    $stmt->close();

    // Produto vinculado à bancada
    $stmt = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) 
        VALUES (?, 'produto_id', ?)
    ");
    $stmt->bind_param("is", $apontamento_id, $produto_id_real);
    $stmt->execute();
    $stmt->close();

    // Destino (Comercialização, Consumo, Descarte)
    $stmt = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) 
        VALUES (?, 'destino', ?)
    ");
    $stmt->bind_param("is", $apontamento_id, $destino_txt);
    $stmt->execute();
    $stmt->close();

    // === Finaliza ===
    $mysqli->commit();

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    if ($mysqli->errno) $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => caderno_erro_msg($e)]);
}
