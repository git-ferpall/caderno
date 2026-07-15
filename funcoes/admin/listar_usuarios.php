<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

[$uid, $perfil] = adminRequirePerfil($mysqli, ['admin', 'representante']);

$q = trim($_GET['q'] ?? '');
$like = $q !== '' ? '%' . $mysqli->real_escape_string($q) . '%' : '';

// ?meus=1 força a visão "meus clientes" (criado_por = eu), mesmo para admin
$somenteMeus = ($_GET['meus'] ?? '') === '1';

if ($perfil === 'admin' && !$somenteMeus) {
    // Todos os usuários registrados + usuários Frutag legados (com propriedade,
    // mas que ainda não logaram desde a criação de usuarios_caderno)
    $filtroU = $q !== ''
        ? " WHERE (u.nome LIKE '$like' OR u.login LIKE '$like' OR u.email LIKE '$like' OR CAST(u.id AS CHAR) LIKE '$like')"
        : '';
    $filtroP = $q !== ''
        ? " AND (p.nome_razao LIKE '$like' OR p.email LIKE '$like' OR CAST(p.user_id AS CHAR) LIKE '$like')"
        : '';

    $sql = "
        SELECT * FROM (
            SELECT u.id, u.origem, u.login, u.email, u.nome, u.perfil,
                   u.ativo, u.criado_por, c.nome AS criado_por_nome,
                   u.criado_em, 1 AS provisionado
            FROM usuarios_caderno u
            LEFT JOIN usuarios_caderno c ON c.id = u.criado_por
            $filtroU

            UNION ALL

            SELECT p.user_id AS id, 'frutag' AS origem, NULL AS login,
                   MAX(p.email) AS email, MAX(p.nome_razao) AS nome, 'usuario' AS perfil,
                   1 AS ativo, NULL AS criado_por, NULL AS criado_por_nome,
                   NULL AS criado_em, 0 AS provisionado
            FROM propriedades p
            WHERE p.user_id NOT IN (SELECT id FROM usuarios_caderno)
            $filtroP
            GROUP BY p.user_id
        ) t
        ORDER BY t.nome IS NULL, t.nome ASC
        LIMIT 300
    ";
} else {
    // Representante: enxerga somente os clientes que ele mesmo cadastrou
    $filtro = $q !== ''
        ? " AND (u.nome LIKE '$like' OR u.login LIKE '$like' OR u.email LIKE '$like' OR CAST(u.id AS CHAR) LIKE '$like')"
        : '';
    $sql = "
        SELECT u.id, u.origem, u.login, u.email, u.nome, u.perfil,
               u.ativo, u.criado_por, NULL AS criado_por_nome,
               u.criado_em, 1 AS provisionado
        FROM usuarios_caderno u
        WHERE u.criado_por = $uid
        $filtro
        ORDER BY u.nome ASC
        LIMIT 300
    ";
}

$rows = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);
adminJson(['ok' => true, 'perfil' => $perfil, 'usuarios' => $rows]);
