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

/**
 * @return list<array{id:int,nome:string}>
 */
function hidroponiaListarProdutosBancada(mysqli $mysqli, int $bancada_id, int $fallback_produto_id = 0): array
{
    $rows = [];

    if ($bancada_id > 0 && hidroponiaTabelaProdutosExiste($mysqli)) {
        $stmt = $mysqli->prepare("
            SELECT p.id, p.nome
            FROM bancada_produtos bp
            INNER JOIN produtos p ON p.id = bp.produto_id
            WHERE bp.bancada_id = ?
            ORDER BY p.nome ASC
        ");
        $stmt->bind_param("i", $bancada_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $rows[] = [
                'id' => (int) $row['id'],
                'nome' => (string) $row['nome'],
            ];
        }
        $stmt->close();
    }

    if ($rows) {
        return $rows;
    }

    if ($fallback_produto_id > 0) {
        $stmt = $mysqli->prepare("SELECT id, nome FROM produtos WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $fallback_produto_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return [
                ['id' => (int) $row['id'], 'nome' => (string) $row['nome']],
            ];
        }
    }

    return [];
}

/**
 * @param list<int> $produto_ids
 */
function hidroponiaSalvarProdutosBancada(mysqli $mysqli, int $bancada_id, array $produto_ids): void
{
    if ($bancada_id <= 0 || !hidroponiaTabelaProdutosExiste($mysqli)) {
        return;
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $produto_ids), static fn ($id) => $id > 0)));
    if (!$ids) {
        return;
    }

    $stmt = $mysqli->prepare("DELETE FROM bancada_produtos WHERE bancada_id = ?");
    $stmt->bind_param("i", $bancada_id);
    $stmt->execute();
    $stmt->close();

    $ins = $mysqli->prepare("INSERT IGNORE INTO bancada_produtos (bancada_id, produto_id) VALUES (?, ?)");
    foreach ($ids as $pid) {
        $ins->bind_param("ii", $bancada_id, $pid);
        $ins->execute();
    }
    $ins->close();
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
    $nomes = array_map(static fn ($p) => $p['nome'] ?? '', $produtos);
    $nomes = array_filter($nomes);
    return $nomes ? implode(', ', $nomes) : $fallback;
}
