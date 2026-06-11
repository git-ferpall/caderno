<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/env.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

function offlineJson(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function offlineAuthUserId(): ?int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    return $user_id ? (int)$user_id : null;
}

function offlineEnsureSchema(mysqli $mysqli): void
{
    $mysqli->query("
        CREATE TABLE IF NOT EXISTS offline_admins (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            nome VARCHAR(255) NULL,
            email VARCHAR(255) NULL,
            adicionado_por INT UNSIGNED NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $mysqli->query("
        CREATE TABLE IF NOT EXISTS offline_usuarios (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            habilitado TINYINT(1) NOT NULL DEFAULT 1,
            habilitado_por INT UNSIGNED NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $res = $mysqli->query("SELECT COUNT(*) AS c FROM offline_admins");
    $count = (int)($res->fetch_assoc()['c'] ?? 0);
    if ($count === 0 && defined('OFFLINE_BOOTSTRAP_ADMINS') && OFFLINE_BOOTSTRAP_ADMINS !== '') {
        $ids = array_filter(array_map('intval', explode(',', OFFLINE_BOOTSTRAP_ADMINS)));
        $stmt = $mysqli->prepare("INSERT IGNORE INTO offline_admins (user_id, nome) VALUES (?, 'Admin bootstrap')");
        foreach ($ids as $id) {
            if ($id > 0) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
        }
        $stmt->close();
    }
}

function offlineIsAdmin(mysqli $mysqli, int $user_id): bool
{
    offlineEnsureSchema($mysqli);
    $stmt = $mysqli->prepare("SELECT 1 FROM offline_admins WHERE user_id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $ok = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $ok;
}

function offlineIsEnabled(mysqli $mysqli, int $user_id): bool
{
    offlineEnsureSchema($mysqli);
    $stmt = $mysqli->prepare("SELECT habilitado FROM offline_usuarios WHERE user_id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Padrão: habilitado para todos; só bloqueia se houver registro explícito com habilitado = 0
    if (!$row) {
        return true;
    }
    return (int)$row['habilitado'] === 1;
}

function offlineRequireAdmin(mysqli $mysqli): int
{
    $user_id = offlineAuthUserId();
    if (!$user_id) {
        offlineJson(['ok' => false, 'msg' => 'Não autenticado.'], 401);
    }
    if (!offlineIsAdmin($mysqli, $user_id)) {
        offlineJson(['ok' => false, 'msg' => 'Acesso negado.'], 403);
    }
    return $user_id;
}

function offlineSalvarEndpoints(): array
{
    return [
        'salvar_adubacao_calcario.php',
        'salvar_adubacao_organica.php',
        'salvar_bancada.php',
        'salvar_clima.php',
        'salvar_colheita.php',
        'salvar_colheita_hidroponia.php',
        'salvar_controle_agua.php',
        'salvar_coleta_analise.php',
        'salvar_defensivo_hidroponia.php',
        'salvar_erradicacao.php',
        'salvar_estufa.php',
        'salvar_fertilizante.php',
        'salvar_fertilizante_hidroponia.php',
        'salvar_fungicida.php',
        'salvar_herbicida.php',
        'salvar_inseticida.php',
        'salvar_irrigacao.php',
        'salvar_manejo_integrado.php',
        'salvar_moscas_frutas.php',
        'salvar_personalizado.php',
        'salvar_plantio.php',
        'salvar_pragas_doencas.php',
        'salvar_revisao_maquinas.php',
        'salvar_semeadura.php',
        'salvar_semeadura_hidroponia.php',
        'salvar_transplantio.php',
        'salvar_visita_tecnica.php',
    ];
}

/** GET buscar_* → chave no bundle dados.php / IndexedDB */
function offlineCatalogMap(): array
{
    return [
        'buscar_areas.php' => 'areas',
        'buscar_produtos.php' => 'produtos',
        'buscar_herbicidas.php' => 'herbicidas',
        'buscar_fertilizantes.php' => 'fertilizantes',
        'buscar_fungicidas.php' => 'fungicidas',
        'buscar_inseticidas.php' => 'inseticidas',
    ];
}

/** Labels para painel de fila offline */
function offlineTipoLabels(): array
{
    return [
        'salvar_plantio' => 'Plantio',
        'salvar_semeadura' => 'Semeadura',
        'salvar_transplantio' => 'Transplantio',
        'salvar_colheita' => 'Colheita',
        'salvar_clima' => 'Climático',
        'salvar_fertilizante' => 'Fertilizante',
        'salvar_herbicida' => 'Herbicida',
        'salvar_fungicida' => 'Fungicida',
        'salvar_inseticida' => 'Inseticida',
        'salvar_adubacao_calcario' => 'Adubação (calcário)',
        'salvar_adubacao_organica' => 'Adubação orgânica',
        'salvar_irrigacao' => 'Irrigação',
        'salvar_controle_agua' => 'Controle de água',
        'salvar_moscas_frutas' => 'Mosca-das-frutas',
        'salvar_pragas_doencas' => 'Pragas e doenças',
        'salvar_manejo_integrado' => 'Manejo integrado',
        'salvar_erradicacao' => 'Erradicação',
        'salvar_revisao_maquinas' => 'Revisão de máquinas',
        'salvar_coleta_analise' => 'Coleta e análise',
        'salvar_visita_tecnica' => 'Visita técnica',
        'salvar_personalizado' => 'Personalizado',
        'salvar_colheita_hidroponia' => 'Colheita hidroponia',
        'salvar_semeadura_hidroponia' => 'Semeadura hidroponia',
        'salvar_fertilizante_hidroponia' => 'Fertilizante hidroponia',
        'salvar_defensivo_hidroponia' => 'Defensivo hidroponia',
        'salvar_estufa' => 'Hidroponia (estufa)',
        'salvar_bancada' => 'Hidroponia (bancada)',
    ];
}

/** Horas até o catálogo local ser considerado desatualizado (aviso ao usuário). */
function offlineCatalogMaxAgeHours(): int
{
    return defined('OFFLINE_CATALOG_MAX_AGE_HOURS') ? max(1, (int)OFFLINE_CATALOG_MAX_AGE_HOURS) : 72;
}

function offlineShellPages(): array
{
    return [
        '/home',
        '/home/apontamento',
        '/home/Plantio',
        '/home/Semeadura',
        '/home/Transplantio',
        '/home/Colheita',
        '/home/Climatico',
        '/home/Fertilizante',
        '/home/Herbicida',
        '/home/Fungicida',
        '/home/Inseticida',
        '/home/AdubacaoCalcario',
        '/home/AdubacaoOrganica',
        '/home/Irrigacao',
        '/home/ControleAgua',
        '/home/MoscaFrutas',
        '/home/PragasDoencas',
        '/home/ManejoIntegrado',
        '/home/Erradicacao',
        '/home/RevisaoMaquinas',
        '/home/ColetaAnalise',
        '/home/VisitaTecnica',
        '/home/Personalizado',
        '/home/hidroponia',
    ];
}
