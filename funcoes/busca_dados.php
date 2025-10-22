<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

function getApontamentosCompletos($mysqli, $user_id) {
    $dados = [];

    // 1️⃣ Propriedade ativa do usuário
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) return []; // Nenhuma propriedade ativa
    $propriedade_id = $prop['id'];

    // 2️⃣ Buscar apontamentos
    $stmt = $mysqli->prepare("SELECT * FROM apontamentos WHERE propriedade_id = ? ORDER BY data DESC, id DESC");
    $stmt->bind_param("i", $propriedade_id);
    $stmt->execute();
    $apontamentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 3️⃣ Itera apontamentos
    foreach ($apontamentos as $ap) {
        $ap_id = $ap['id'];

        $stmt = $mysqli->prepare("SELECT campo, valor FROM apontamento_detalhes WHERE apontamento_id = ?");
        $stmt->bind_param("i", $ap_id);
        $stmt->execute();
        $detalhes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $campos = [];
        $areas = [];
        $produtos = [];

        foreach ($detalhes as $d) {
            $campo = $d['campo'];
            $valor = $d['valor'];

            if ($campo === 'area_id') {
                $areas[] = (int)$valor;
            } elseif ($campo === 'produto_id') {
                $produtos[] = (int)$valor;
            } else {
                $campos[$campo] = $valor;
            }
        }

        // Buscar nomes das áreas
        $areas_nome = [];
        if (!empty($areas)) {
            $ids = implode(',', array_map('intval', $areas));
            $query = "SELECT id, nome FROM areas WHERE id IN ($ids)";
            $res = $mysqli->query($query);
            while ($row = $res->fetch_assoc()) {
                $areas_nome[] = $row['nome'];
            }
        }

        // Buscar nomes dos produtos
        $produtos_nome = [];
        if (!empty($produtos)) {
            $ids = implode(',', array_map('intval', $produtos));
            $query = "SELECT id, nome FROM produtos WHERE id IN ($ids)";
            $res = $mysqli->query($query);
            while ($row = $res->fetch_assoc()) {
                $produtos_nome[] = $row['nome'];
            }
        }

        $dados[] = [
            'id' => $ap['id'],
            'tipo' => $ap['tipo'],
            'data' => $ap['data'],
            'quantidade' => $ap['quantidade'],
            'previsao' => $ap['previsao'],
            'observacoes' => $ap['observacoes'],
            'status' => $ap['status'],
            'detalhes' => $campos,
            'areas' => $areas_nome,
            'produtos' => $produtos_nome
        ];
    }

    return $dados;
}
?>
