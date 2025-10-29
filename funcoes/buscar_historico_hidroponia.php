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
    if (!$user_id) throw new Exception('Usuário não autenticado.');

    // === Propriedade ativa ===
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $prop = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$prop) throw new Exception('Nenhuma propriedade ativa encontrada.');
    $propriedade_id = $prop['id'];

    // === Parâmetros ===
    $estufa_id = $_POST['estufa_id'] ?? null;
    $bancada_nome = $_POST['bancada_nome'] ?? null;

    if (!$estufa_id || !$bancada_nome) {
        throw new Exception('Estufa ou bancada não informada.');
    }

    // === Busca área vinculada à bancada ===
    $stmt = $mysqli->prepare("
        SELECT id, area_id, produto_id
        FROM bancadas
        WHERE estufa_id = ? AND nome LIKE CONCAT('%', ?, '%')
        LIMIT 1
    ");
    $stmt->bind_param("is", $estufa_id, $bancada_nome);
    $stmt->execute();
    $bancada = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$bancada) {
        throw new Exception('Bancada não encontrada.');
    }

    $area_id = $bancada['area_id'];

    // === Consulta dos apontamentos vinculados à área ===
    $sql = "
        SELECT 
            a.id,
            a.tipo,
            a.data,
            a.data_conclusao,
            a.quantidade,
            a.status,
            a.observacoes,
            (
                SELECT ad2.valor 
                FROM apontamento_detalhes ad2 
                WHERE ad2.apontamento_id = a.id AND ad2.campo = 'defensivo' 
                LIMIT 1
            ) AS defensivo,
            (
                SELECT ad3.valor 
                FROM apontamento_detalhes ad3 
                WHERE ad3.apontamento_id = a.id AND ad3.campo = 'motivo' 
                LIMIT 1
            ) AS motivo,
            (
                SELECT ad4.valor 
                FROM apontamento_detalhes ad4 
                WHERE ad4.apontamento_id = a.id AND ad4.campo = 'destino' 
                LIMIT 1
            ) AS destino
        FROM apontamentos a
        JOIN apontamento_detalhes ad ON ad.apontamento_id = a.id
        WHERE a.propriedade_id = ? 
          AND ad.campo = 'area_id' 
          AND ad.valor = ?
        ORDER BY a.data DESC, a.id DESC
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $propriedade_id, $area_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $historico = [];
    while ($row = $res->fetch_assoc()) {
        $tipo = ucfirst($row['tipo']);
        $data = date('d/m/Y', strtotime($row['data']));
        $hora = $row['data_conclusao'] ? date('H:i', strtotime($row['data_conclusao'])) : '';
        $qtd = ($row['quantidade'] > 0) ? $row['quantidade'] : '—';

        $descricao = '';
        if ($row['tipo'] === 'defensivo') {
            $descricao = "Defensivo: {$row['defensivo']} ({$row['motivo']})";
        } elseif ($row['tipo'] === 'fertilizante') {
            $descricao = "Fertilizante aplicado";
        } elseif ($row['tipo'] === 'colheita') {
            $descricao = "Colheita → {$row['destino']}";
        }

        $historico[] = [
            'id' => $row['id'],
            'tipo' => $tipo,
            'data' => $data,
            'hora' => $hora,
            'descricao' => $descricao,
            'quantidade' => $qtd,
            'status' => ucfirst($row['status']),
            'obs' => $row['observacoes'] ?: '—'
        ];
    }

    echo json_encode(['ok' => true, 'historico' => $historico]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
