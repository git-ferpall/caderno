<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

/**
 * Retorna todas as estufas e bancadas da propriedade ativa
 */
function carregarHidroponia(): array {
    global $mysqli;

    session_start();

    // 🔐 1️⃣ Identifica usuário
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) {
        return ['ok' => false, 'err' => 'Usuário não autenticado'];
    }

    // 🏠 2️⃣ Busca propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if (!$prop) {
        return ['ok' => false, 'err' => 'Nenhuma propriedade ativa encontrada'];
    }

    $propriedade_id = $prop['id'];

    // 🌿 3️⃣ Busca estufas da propriedade
    $estufas = [];
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

        // 🪴 4️⃣ Busca bancadas vinculadas à estufa
        $bancadas = [];
        $q_banc = $mysqli->prepare("
            SELECT id, nome, cultura, obs 
            FROM bancadas 
            WHERE estufa_id = ?
            ORDER BY nome ASC
        ");
        $q_banc->bind_param("i", $estufa['id']);
        $q_banc->execute();
        $r_banc = $q_banc->get_result();

        while ($bancada = $r_banc->fetch_assoc()) {
            $bancadas[] = [
                'id'      => (int)$bancada['id'],
                'nome'    => $bancada['nome'],
                'cultura' => $bancada['cultura'],
                'obs'     => $bancada['obs']
            ];
        }

        $estufas[] = [
            'id'       => (int)$estufa['id'],
            'nome'     => $estufa['nome'],
            'area_m2'  => $estufa['area_m2'],
            'obs'      => $estufa['obs'],
            'bancadas' => $bancadas
        ];

        $q_banc->close();
    }

    $q_estufas->close();

    return ['ok' => true, 'estufas' => $estufas];
}

// 🔄 Se for acessado diretamente (ex: via fetch)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(carregarHidroponia());
    exit;
}
