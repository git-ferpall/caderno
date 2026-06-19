<?php
declare(strict_types=1);

require_once __DIR__ . '/carencia.php';

function fsNormalizarNome(string $nome): string
{
    $nome = mb_strtolower(trim($nome), 'UTF-8');
    $nome = preg_replace('/\s+/u', ' ', $nome) ?? $nome;
    $trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome);
    if ($trans !== false) {
        $nome = strtolower($trans);
    }
    return preg_replace('/[^a-z0-9 ]/', '', $nome) ?? $nome;
}

function fsTabelaAgrofitExiste(mysqli $mysqli): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $res = $mysqli->query("SHOW TABLES LIKE 'produtos_fitossanitarios'");
    $cache = $res && $res->num_rows > 0;
    return $cache;
}

/** Sincroniza catálogo local (herbicidas/fungicidas/inseticidas) → produtos_fitossanitarios. */
function fsSincronizarAgrofitDesdeCatalogo(mysqli $mysqli): array
{
    if (!fsTabelaAgrofitExiste($mysqli)) {
        return ['ok' => false, 'msg' => 'Tabela produtos_fitossanitarios não encontrada. Execute fitossanitaria_fase1.sql'];
    }

    try {
        $map = [
            'herbicida' => ['tabela' => 'herbicidas', 'ativo' => "status = 'ativo'"],
            'fungicida' => ['tabela' => 'fungicidas', 'ativo' => 'ativo = 1'],
            'inseticida' => ['tabela' => 'inseticidas', 'ativo' => 'ativo = 1'],
        ];

        $inseridos = 0;
        $atualizados = 0;

        foreach ($map as $tipo => $cfg) {
            $tabela = $cfg['tabela'];
            if (!fsColunasCarenciaExistem($mysqli, $tabela)) {
                continue;
            }

            $sql = "
                SELECT nome, carencia_dias, ingrediente_ativo
                FROM `{$tabela}`
                WHERE {$cfg['ativo']}
            ";
            $res = $mysqli->query($sql);
            if (!$res) {
                continue;
            }

            $stmt = $mysqli->prepare('
                INSERT INTO produtos_fitossanitarios (tipo, nome, ingrediente_ativo, carencia_dias, ativo)
                VALUES (?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE
                    ingrediente_ativo = COALESCE(NULLIF(VALUES(ingrediente_ativo), ''), ingrediente_ativo),
                    carencia_dias = IF(VALUES(carencia_dias) > 0, VALUES(carencia_dias), carencia_dias),
                    atualizado_em = NOW()
            ');

            while ($row = $res->fetch_assoc()) {
                $nome = trim((string) ($row['nome'] ?? ''));
                if ($nome === '') {
                    continue;
                }
                $ia = trim((string) ($row['ingrediente_ativo'] ?? ''));
                $carencia = isset($row['carencia_dias']) && $row['carencia_dias'] !== '' && $row['carencia_dias'] !== null
                    ? (int) $row['carencia_dias'] : 0;
                $stmt->bind_param('sssi', $tipo, $nome, $ia, $carencia);
                $stmt->execute();
                if ($stmt->affected_rows === 1) {
                    $inseridos++;
                } elseif ($stmt->affected_rows === 2) {
                    $atualizados++;
                }
            }
            $stmt->close();
        }

        return [
            'ok' => true,
            'msg' => "Catálogo AGROFIT local sincronizado: {$inseridos} novo(s), {$atualizados} atualizado(s).",
            'inseridos' => $inseridos,
            'atualizados' => $atualizados,
        ];
    } catch (Throwable $e) {
        error_log('fitossanitaria agrofit sync: ' . $e->getMessage());
        return ['ok' => false, 'msg' => 'Erro ao sincronizar catálogo AGROFIT. Verifique fitossanitaria_fase1.sql.'];
    }
}

function fsBuscarProdutoAgrofit(mysqli $mysqli, string $tipo, string $nome): ?array
{
    if (!fsTabelaAgrofitExiste($mysqli) || trim($nome) === '') {
        return null;
    }

    $tipo = preg_replace('/[^a-z_]/', '', $tipo) ?? $tipo;
    if (ctype_digit($nome)) {
        return null;
    }

    try {
        $stmt = $mysqli->prepare('
            SELECT id, tipo, nome, ingrediente_ativo, carencia_dias, registro_mapa, culturas, observacoes
            FROM produtos_fitossanitarios
            WHERE tipo = ? AND nome = ? AND ativo = 1
            LIMIT 1
        ');
        $stmt->bind_param('ss', $tipo, $nome);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $like = '%' . $nome . '%';
            $stmt = $mysqli->prepare('
                SELECT id, tipo, nome, ingrediente_ativo, carencia_dias, registro_mapa, culturas, observacoes
                FROM produtos_fitossanitarios
                WHERE tipo = ? AND nome LIKE ? AND ativo = 1
                LIMIT 1
            ');
            $stmt->bind_param('ss', $tipo, $like);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('fitossanitaria agrofit busca: ' . $e->getMessage());
        return null;
    }

    return $row ?: null;
}

/** @return string[] */
function fsParseCulturasAgrofit(?string $culturas): array
{
    if ($culturas === null || trim($culturas) === '') {
        return [];
    }
    $decoded = json_decode($culturas, true);
    if (is_array($decoded)) {
        return array_values(array_filter(array_map('trim', $decoded)));
    }
    return array_values(array_filter(array_map('trim', preg_split('/[,;|]/', $culturas) ?: [])));
}

function fsVerificarProdutoParaCultura(mysqli $mysqli, string $tipo, string $produtoNome, string $culturaNome): array
{
    $produto = fsBuscarProdutoAgrofit($mysqli, $tipo, $produtoNome);
    if (!$produto) {
        return [
            'autorizado' => null,
            'status' => 'nao_cadastrado',
            'resumo' => 'Produto não encontrado no catálogo técnico local. Cadastre ou sincronize AGROFIT.',
            'produto' => null,
        ];
    }

    $culturas = fsParseCulturasAgrofit($produto['culturas'] ?? null);
    if (!$culturas) {
        return [
            'autorizado' => null,
            'status' => 'sem_culturas',
            'resumo' => 'Produto cadastrado, mas sem culturas autorizadas informadas. Validar no AGROFIT/MAPA.',
            'produto' => $produto,
        ];
    }

    $normCultura = fsNormalizarNome($culturaNome);
    $autorizado = false;
    foreach ($culturas as $c) {
        if (str_contains(fsNormalizarNome($c), $normCultura) || str_contains($normCultura, fsNormalizarNome($c))) {
            $autorizado = true;
            break;
        }
    }

    return [
        'autorizado' => $autorizado,
        'status' => $autorizado ? 'autorizado' : 'nao_autorizado',
        'resumo' => $autorizado
            ? "Produto registrado para cultura similar a \"{$culturaNome}\"."
            : "Produto cadastrado, mas cultura \"{$culturaNome}\" não consta na lista autorizada.",
        'produto' => $produto,
        'culturas_cadastradas' => $culturas,
    ];
}

/** Verifica últimas aplicações da área contra culturas. */
function fsVerificarAplicacoesAgrofit(mysqli $mysqli, array $aplicacoes, array $culturas): array
{
    $alertas = [];
    $detalhes = [];
    $culturaRef = $culturas[0] ?? '';

    foreach (array_slice($aplicacoes, 0, 5) as $app) {
        $produto = (string) ($app['produto'] ?? '');
        $tipo = (string) ($app['tipo'] ?? '');
        if ($produto === '' || !in_array($tipo, fsTiposComCarencia(), true)) {
            continue;
        }
        $check = fsVerificarProdutoParaCultura($mysqli, $tipo, $produto, $culturaRef ?: 'cultura');
        $detalhes[] = array_merge(['aplicacao' => $app], $check);
        if ($check['status'] === 'nao_autorizado') {
            $alertas[] = "{$produto}: cultura não autorizada no cadastro técnico.";
        } elseif ($check['status'] === 'nao_cadastrado') {
            $alertas[] = "{$produto}: ausente no catálogo AGROFIT local.";
        }
    }

    return [
        'detalhes' => $detalhes,
        'alertas' => $alertas,
        'resumo' => $alertas
            ? count($alertas) . ' alerta(s) de registro/cultura.'
            : ($detalhes ? 'Produtos recentes conferidos no catálogo local.' : 'Sem aplicações para conferir.'),
    ];
}
