<?php
declare(strict_types=1);

/**
 * Helpers hidroponia — produtos por bancada e links QR.
 */

function hidroponiaTabelaProdutosExiste(mysqli $mysqli): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $r = $mysqli->query("SHOW TABLES LIKE 'bancada_produtos'");
    $cache = ($r && $r->num_rows > 0);
    return $cache;
}

function hidroponiaColunaProdutosExiste(mysqli $mysqli, string $coluna): bool
{
    static $cache = [];
    if (isset($cache[$coluna])) {
        return $cache[$coluna];
    }
    if (!hidroponiaTabelaProdutosExiste($mysqli)) {
        $cache[$coluna] = false;
        return false;
    }
    $coluna = preg_replace('/[^a-z_]/', '', $coluna);
    $r = $mysqli->query("SHOW COLUMNS FROM bancada_produtos LIKE '{$coluna}'");
    $cache[$coluna] = ($r && $r->num_rows > 0);
    return $cache[$coluna];
}

/** @return list<string> */
function hidroponiaPaletaCores(): array
{
    return ['#13b0a6', '#e91e63', '#ff9800', '#9c27b0', '#4caf50', '#2196f3', '#795548', '#607d8b'];
}

/**
 * @param list<array{id:int,nome:string,area_m2?:float|null,percentual?:float|null,cor?:string}> $produtos
 * @return list<array{id:int,nome:string,area_m2:float,percentual:float,cor:string}>
 */
function hidroponiaEnriquecerProdutosArea(array $produtos, float $area_total): array
{
    $cores = hidroponiaPaletaCores();
    $count = count($produtos);
    $out = [];

    foreach ($produtos as $i => $p) {
        $area_m2 = isset($p['area_m2']) ? (float) $p['area_m2'] : 0.0;
        $percentual = isset($p['percentual']) ? (float) $p['percentual'] : 0.0;

        if ($area_total > 0) {
            if ($area_m2 <= 0 && $percentual > 0) {
                $area_m2 = round($area_total * $percentual / 100, 2);
            } elseif ($percentual <= 0 && $area_m2 > 0) {
                $percentual = round($area_m2 / $area_total * 100, 2);
            } elseif ($area_m2 <= 0 && $percentual <= 0 && $count > 0) {
                $percentual = round(100 / $count, 2);
                $area_m2 = round($area_total / $count, 2);
            }
        } elseif ($percentual <= 0 && $count > 0) {
            $percentual = round(100 / $count, 2);
        }

        $out[] = [
            'id' => (int) ($p['id'] ?? 0),
            'nome' => (string) ($p['nome'] ?? ''),
            'area_m2' => $area_m2,
            'percentual' => $percentual,
            'cor' => (string) ($p['cor'] ?? $cores[$i % count($cores)]),
        ];
    }

    return $out;
}

/**
 * @return list<array{id:int,nome:string,area_m2:float,percentual:float,cor:string}>
 */
function hidroponiaListarProdutosBancada(mysqli $mysqli, int $bancada_id, int $fallback_produto_id = 0, float $area_total = 0): array
{
    $rows = [];
    $tem_area = hidroponiaColunaProdutosExiste($mysqli, 'area_m2');

    if ($bancada_id > 0 && hidroponiaTabelaProdutosExiste($mysqli)) {
        $extra = $tem_area ? ', bp.area_m2, bp.percentual' : '';
        $stmt = $mysqli->prepare("
            SELECT p.id, p.nome{$extra}
            FROM bancada_produtos bp
            INNER JOIN produtos p ON p.id = bp.produto_id
            WHERE bp.bancada_id = ?
            ORDER BY p.nome ASC
        ");
        $stmt->bind_param("i", $bancada_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $item = [
                'id' => (int) $row['id'],
                'nome' => (string) $row['nome'],
            ];
            if ($tem_area) {
                $item['area_m2'] = $row['area_m2'] !== null ? (float) $row['area_m2'] : 0.0;
                $item['percentual'] = $row['percentual'] !== null ? (float) $row['percentual'] : 0.0;
            }
            $rows[] = $item;
        }
        $stmt->close();
    }

    if (!$rows && $fallback_produto_id > 0) {
        $stmt = $mysqli->prepare("SELECT id, nome FROM produtos WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $fallback_produto_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $rows[] = ['id' => (int) $row['id'], 'nome' => (string) $row['nome']];
        }
    }

    return hidroponiaEnriquecerProdutosArea($rows, $area_total);
}

/**
 * @param list<int> $produto_ids
 */
function hidroponiaSalvarProdutosBancada(mysqli $mysqli, int $bancada_id, array $produto_ids, float $area_total = 0): void
{
    if ($bancada_id <= 0 || !hidroponiaTabelaProdutosExiste($mysqli)) {
        return;
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $produto_ids), static fn ($id) => $id > 0)));
    if (!$ids) {
        return;
    }

    $count = count($ids);
    $items = [];
    foreach ($ids as $i => $pid) {
        $pct = $count > 0 ? round(100 / $count, 2) : 0.0;
        $m2 = $area_total > 0 && $count > 0 ? round($area_total / $count, 2) : 0.0;
        $items[] = [
            'produto_id' => $pid,
            'area_m2' => $m2,
            'percentual' => $pct,
        ];
    }

    hidroponiaSalvarProdutosBancadaDetalhe($mysqli, $bancada_id, $items);
}

/**
 * @param list<array{produto_id:int,area_m2?:float,percentual?:float}> $items
 */
function hidroponiaSalvarProdutosBancadaDetalhe(mysqli $mysqli, int $bancada_id, array $items, float $area_total = 0): void
{
    if ($bancada_id <= 0 || !hidroponiaTabelaProdutosExiste($mysqli)) {
        return;
    }

    $normalizados = [];
    foreach ($items as $item) {
        $pid = (int) ($item['produto_id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }
        $normalizados[] = [
            'produto_id' => $pid,
            'area_m2' => isset($item['area_m2']) ? (float) $item['area_m2'] : 0.0,
            'percentual' => isset($item['percentual']) ? (float) $item['percentual'] : 0.0,
        ];
    }

    if (!$normalizados) {
        return;
    }

    if ($area_total > 0) {
        $enriquecidos = hidroponiaEnriquecerProdutosArea(
            array_map(static fn ($it) => [
                'id' => $it['produto_id'],
                'nome' => '',
                'area_m2' => $it['area_m2'],
                'percentual' => $it['percentual'],
            ], $normalizados),
            $area_total
        );
        foreach ($normalizados as $i => &$it) {
            $it['area_m2'] = $enriquecidos[$i]['area_m2'];
            $it['percentual'] = $enriquecidos[$i]['percentual'];
        }
        unset($it);
    }

    $stmt = $mysqli->prepare("DELETE FROM bancada_produtos WHERE bancada_id = ?");
    $stmt->bind_param("i", $bancada_id);
    $stmt->execute();
    $stmt->close();

    $tem_area = hidroponiaColunaProdutosExiste($mysqli, 'area_m2');

    if ($tem_area) {
        $ins = $mysqli->prepare("INSERT IGNORE INTO bancada_produtos (bancada_id, produto_id, area_m2, percentual) VALUES (?, ?, ?, ?)");
        foreach ($normalizados as $it) {
            $ins->bind_param("iidd", $bancada_id, $it['produto_id'], $it['area_m2'], $it['percentual']);
            $ins->execute();
        }
    } else {
        $ins = $mysqli->prepare("INSERT IGNORE INTO bancada_produtos (bancada_id, produto_id) VALUES (?, ?)");
        foreach ($normalizados as $it) {
            $ins->bind_param("ii", $bancada_id, $it['produto_id']);
            $ins->execute();
        }
    }
    $ins->close();

    $primeiro = (int) $normalizados[0]['produto_id'];
    $upd = $mysqli->prepare("UPDATE bancadas SET produto_id = ? WHERE id = ?");
    $upd->bind_param("ii", $primeiro, $bancada_id);
    $upd->execute();
    $upd->close();
}

function hidroponiaUrlBancada(int $bancada_id, ?string $host = null): string
{
    $base = $host ?: ($_SERVER['HTTP_HOST'] ?? 'caderno.frutag.com.br');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $base . '/home/hidroponia?b=' . $bancada_id;
}

function hidroponiaFormatCulturas(array $produtos, string $fallback = 'Não informado'): string
{
    if (!$produtos) {
        return $fallback;
    }
    $partes = [];
    foreach ($produtos as $p) {
        $nome = trim((string) ($p['nome'] ?? ''));
        if ($nome === '') {
            continue;
        }
        $pct = isset($p['percentual']) ? (float) $p['percentual'] : 0;
        $m2 = isset($p['area_m2']) ? (float) $p['area_m2'] : 0;
        if ($pct > 0) {
            $partes[] = $nome . ' (' . rtrim(rtrim(number_format($pct, 1, ',', '.'), '0'), ',') . '%)';
        } elseif ($m2 > 0) {
            $partes[] = $nome . ' (' . rtrim(rtrim(number_format($m2, 2, ',', '.'), '0'), ',') . ' m²)';
        } else {
            $partes[] = $nome;
        }
    }
    return $partes ? implode(', ', $partes) : $fallback;
}

function hidroponiaValidarBancadaUsuario(mysqli $mysqli, int $user_id, int $bancada_id): ?array
{
    $stmt = $mysqli->prepare("
        SELECT b.id, b.nome, b.estufa_id, b.area_id, COALESCE(a.tamanho, 0) AS area_m2, e.nome AS estufa_nome
        FROM bancadas b
        INNER JOIN estufas e ON e.id = b.estufa_id
        INNER JOIN propriedades p ON p.id = e.propriedade_id AND p.user_id = ? AND p.ativo = 1
        LEFT JOIN areas a ON a.id = b.area_id
        WHERE b.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $user_id, $bancada_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
