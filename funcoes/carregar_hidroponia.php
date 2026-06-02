<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/hidroponia_helpers.php';

/**
 * Retorna todas as estufas e bancadas da propriedade ativa,
 * incluindo o nome do produto vinculado.
 */
function carregarHidroponia(): array {
    global $mysqli;

    // ⚠️ Importante: NÃO definir header aqui
    session_start();

    // 🔐 1️⃣ Identifica usuário autenticado
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        try {
            $payload = verify_jwt();
            $user_id = $payload['sub'] ?? null;
        } catch (Exception $e) {
            return ['ok' => false, 'err' => 'Falha ao validar token de autenticação.'];
        }
    }
    if (!$user_id) {
        return ['ok' => false, 'err' => 'Usuário não autenticado.'];
    }

    // 🏠 2️⃣ Busca propriedade ativa
    $stmt = $mysqli->prepare("
        SELECT id 
        FROM propriedades 
        WHERE user_id = ? AND ativo = 1 
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) {
        return ['ok' => false, 'err' => 'Nenhuma propriedade ativa encontrada.'];
    }

    $propriedade_id = (int)$prop['id'];
    $estufas = [];

    // 🌿 3️⃣ Busca estufas da propriedade
    $q_estufas = $mysqli->prepare("
        SELECT id, nome, area_m2, obs 
        FROM estufas 
        WHERE propriedade_id = ?
        ORDER BY nome ASC
    ");
    $q_estufas->bind_param("i", $propriedade_id);
    $q_estufas->execute();
    $r_estufas = $q_estufas->get_result();

    while ($estufa = $r_estufas->fetch_assoc()) {
        $estufa_id = (int)$estufa['id'];

        // 🪴 4️⃣ Busca bancadas + nome do produto
        $bancadas = [];
        $q_banc = $mysqli->prepare("
            SELECT 
                b.id,
                b.nome,
                b.produto_id,
                COALESCE(p.nome, 'Não informado') AS produto_nome,
                b.obs,
                COALESCE(a.tamanho, 0) AS area_m2
            FROM bancadas b
            LEFT JOIN produtos p ON p.id = b.produto_id
            LEFT JOIN areas a ON a.id = b.area_id
            WHERE b.estufa_id = ?
            ORDER BY b.nome ASC
        ");
        $q_banc->bind_param("i", $estufa_id);
        $q_banc->execute();
        $r_banc = $q_banc->get_result();

        while ($bancada = $r_banc->fetch_assoc()) {
            $bancada_id = (int) $bancada['id'];
            $area_m2 = (float) ($bancada['area_m2'] ?? 0);
            $produtos = hidroponiaListarProdutosBancada($mysqli, $bancada_id, (int) $bancada['produto_id'], $area_m2);
            $bancadas[] = [
                'id'          => $bancada_id,
                'nome'        => $bancada['nome'],
                'produto_id'  => $produtos ? (int) $produtos[0]['id'] : (int) $bancada['produto_id'],
                'produtos'    => $produtos,
                'area_m2'     => $area_m2,
                'cultura'     => hidroponiaFormatCulturas($produtos, $bancada['produto_nome'] ?? 'Não informado'),
                'obs'         => $bancada['obs'] ?? '',
            ];
        }

        $q_banc->close();

        $estufas[] = [
            'id'       => $estufa_id,
            'nome'     => $estufa['nome'],
            'area_m2'  => $estufa['area_m2'] ?? '',
            'obs'      => $estufa['obs'] ?? '',
            'bancadas' => $bancadas
        ];
    }

    $q_estufas->close();

    return ['ok' => true, 'estufas' => $estufas];
}

// 🔄 5️⃣ Se for chamado diretamente (via fetch no front-end)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        echo json_encode(carregarHidroponia(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        echo json_encode([
            'ok' => false,
            'err' => 'Erro inesperado: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
