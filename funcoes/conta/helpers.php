<?php
declare(strict_types=1);

/**
 * Helpers dos endpoints de gestão de usuários da conta (funcionários).
 * Podem gerenciar: o dono da conta e funcionários com papel_conta = 'admin'.
 */

require_once __DIR__ . '/../../configuracao/env.php';
require_once __DIR__ . '/../../configuracao/usuarios_local.php'; // conexão ($mysqli) + helpers
require_once __DIR__ . '/../../sso/verify_jwt.php';

function contaJson(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Autentica e retorna [contaId, papel, funcId, payload].
 * papel: 'dono' | 'admin' | 'apontador' (verify_jwt já revalidou o funcionário).
 */
function contaAuth(): array
{
    $payload = verify_jwt();
    $contaId = (int)($payload['sub'] ?? 0);
    if ($contaId <= 0) {
        contaJson(['ok' => false, 'msg' => 'Não autenticado.'], 401);
    }
    $funcId = (int)($payload['func_id'] ?? 0);
    $papel  = $funcId > 0 ? (string)($payload['func_papel'] ?? 'apontador') : 'dono';
    return [$contaId, $papel, $funcId, $payload];
}

/** Exige permissão de gestão da conta (dono ou funcionário admin). */
function contaRequireGestao(): array
{
    [$contaId, $papel, $funcId, $payload] = contaAuth();
    if (!in_array($papel, ['dono', 'admin'], true)) {
        contaJson(['ok' => false, 'msg' => 'Seu acesso permite apenas registrar apontamentos.'], 403);
    }
    return [$contaId, $papel, $funcId, $payload];
}

/** Busca um funcionário garantindo que pertence à conta. */
function contaBuscarFuncionario(mysqli $mysqli, int $funcionarioId, int $contaId): ?array
{
    $stmt = $mysqli->prepare("SELECT * FROM usuarios_caderno WHERE id = ? AND conta_pai = ? LIMIT 1");
    $stmt->bind_param('ii', $funcionarioId, $contaId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/* ============================================================
 * Propriedades permitidas por funcionário
 * ============================================================ */

function contaFuncionarioPropriedadesEnsureSchema(mysqli $mysqli): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    $mysqli->query("
        CREATE TABLE IF NOT EXISTS conta_funcionario_propriedades (
            funcionario_id INT UNSIGNED NOT NULL,
            propriedade_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (funcionario_id, propriedade_id),
            KEY idx_cfp_propriedade (propriedade_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

/** IDs das propriedades da conta (dono dos dados). */
function contaListarPropriedadesIds(mysqli $mysqli, int $contaId): array
{
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? ORDER BY nome_razao ASC");
    $stmt->bind_param('i', $contaId);
    $stmt->execute();
    $ids = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id'));
    $stmt->close();
    return $ids;
}

/** Lista propriedades da conta para exibição (id, nome_razao, endereco_cidade, endereco_uf). */
function contaListarPropriedades(mysqli $mysqli, int $contaId): array
{
    $stmt = $mysqli->prepare("
        SELECT id, nome_razao, endereco_cidade, endereco_uf, ativo
        FROM propriedades WHERE user_id = ?
        ORDER BY nome_razao ASC
    ");
    $stmt->bind_param('i', $contaId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Propriedades permitidas ao funcionário.
 * null  = sem restrição (todas as propriedades da conta)
 * array = somente esses IDs
 */
function contaFuncionarioPropriedadesIds(mysqli $mysqli, int $funcionarioId): ?array
{
    contaFuncionarioPropriedadesEnsureSchema($mysqli);
    $stmt = $mysqli->prepare("SELECT propriedade_id FROM conta_funcionario_propriedades WHERE funcionario_id = ?");
    $stmt->bind_param('i', $funcionarioId);
    $stmt->execute();
    $ids = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'propriedade_id'));
    $stmt->close();
    return $ids === [] ? null : $ids;
}

/** Salva as propriedades permitidas. Array vazio ou com todas = remove restrição (acesso total). */
function contaFuncionarioSalvarPropriedades(mysqli $mysqli, int $funcionarioId, int $contaId, array $propIds): void
{
    contaFuncionarioPropriedadesEnsureSchema($mysqli);

    $todas = contaListarPropriedadesIds($mysqli, $contaId);
    if ($todas === []) {
        throw new InvalidArgumentException('Cadastre ao menos uma propriedade antes de criar acessos.');
    }

    $propIds = array_values(array_unique(array_map('intval', $propIds)));
    $propIds = array_values(array_intersect($propIds, $todas));

    if ($propIds === []) {
        throw new InvalidArgumentException('Selecione ao menos uma propriedade para este acesso.');
    }

    // Todas selecionadas = sem restrição (comportamento padrão)
    sort($propIds);
    $todasSorted = $todas;
    sort($todasSorted);
    if ($propIds === $todasSorted) {
        $stmt = $mysqli->prepare('DELETE FROM conta_funcionario_propriedades WHERE funcionario_id = ?');
        $stmt->bind_param('i', $funcionarioId);
        $stmt->execute();
        $stmt->close();
        return;
    }

    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare('DELETE FROM conta_funcionario_propriedades WHERE funcionario_id = ?');
        $stmt->bind_param('i', $funcionarioId);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare('INSERT INTO conta_funcionario_propriedades (funcionario_id, propriedade_id) VALUES (?, ?)');
        foreach ($propIds as $pid) {
            $stmt->bind_param('ii', $funcionarioId, $pid);
            $stmt->execute();
        }
        $stmt->close();
        $mysqli->commit();
    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }
}

function contaFuncionarioPropriedadePermitida(mysqli $mysqli, int $funcionarioId, int $contaId, int $propriedadeId): bool
{
    if ($propriedadeId <= 0) return false;
    $permitidas = contaFuncionarioPropriedadesIds($mysqli, $funcionarioId);
    if ($permitidas === null) return true;

    if (!in_array($propriedadeId, $permitidas, true)) return false;

    $stmt = $mysqli->prepare('SELECT id FROM propriedades WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $propriedadeId, $contaId);
    $stmt->execute();
    $ok = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $ok;
}

/** Garante que a propriedade ativa da conta é uma das permitidas ao funcionário. */
function contaFuncionarioGarantirPropriedadeAtiva(mysqli $mysqli, int $contaId, int $funcionarioId): void
{
    $permitidas = contaFuncionarioPropriedadesIds($mysqli, $funcionarioId);
    if ($permitidas === null || $permitidas === []) return;

    $stmt = $mysqli->prepare('SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1');
    $stmt->bind_param('i', $contaId);
    $stmt->execute();
    $ativa = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($ativa && in_array((int)$ativa['id'], $permitidas, true)) return;

    $mysqli->query('UPDATE propriedades SET ativo = 0 WHERE user_id = ' . (int)$contaId);
    $firstId = $permitidas[0];
    $stmt = $mysqli->prepare('UPDATE propriedades SET ativo = 1 WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $firstId, $contaId);
    $stmt->execute();
    $stmt->close();
}

/** SQL extra para filtrar propriedades de um funcionário (ex.: " AND id IN (1,2)"). */
function contaFuncionarioSqlFiltroPropriedades(mysqli $mysqli, int $funcionarioId, string $coluna = 'id'): string
{
    $permitidas = contaFuncionarioPropriedadesIds($mysqli, $funcionarioId);
    if ($permitidas === null) return '';
    if ($permitidas === []) return ' AND 1=0';
    return ' AND ' . $coluna . ' IN (' . implode(',', $permitidas) . ')';
}
