<?php

/**
 * Normaliza lista de IDs inteiros positivos (sem duplicatas).
 */
function caderno_normalizar_ids(array $ids): array
{
    $out = [];
    foreach ($ids as $id) {
        $n = (int) $id;
        if ($n > 0) {
            $out[$n] = $n;
        }
    }
    return array_values($out);
}

/**
 * Garante que todas as áreas pertencem ao usuário (e à propriedade, se informada).
 *
 * @throws InvalidArgumentException
 */
function caderno_validar_areas_usuario(mysqli $mysqli, int $user_id, array $area_ids, ?int $propriedade_id = null): void
{
    $ids = caderno_normalizar_ids($area_ids);
    if ($ids === []) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT COUNT(*) AS c FROM areas WHERE user_id = ? AND id IN ($placeholders)";
    $types = 'i' . str_repeat('i', count($ids));
    $params = array_merge([$user_id], $ids);

    if ($propriedade_id !== null && $propriedade_id > 0) {
        $sql .= ' AND propriedade_id = ?';
        $types .= 'i';
        $params[] = $propriedade_id;
    }

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    if ($count !== count($ids)) {
        throw new InvalidArgumentException('area_invalida');
    }
}

/**
 * Garante que todos os produtos pertencem ao usuário.
 *
 * @throws InvalidArgumentException
 */
function caderno_validar_produtos_usuario(mysqli $mysqli, int $user_id, array $produto_ids): void
{
    $ids = caderno_normalizar_ids($produto_ids);
    if ($ids === []) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT COUNT(*) AS c FROM produtos WHERE user_id = ? AND id IN ($placeholders)";
    $types = 'i' . str_repeat('i', count($ids));
    $params = array_merge([$user_id], $ids);

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    if ($count !== count($ids)) {
        throw new InvalidArgumentException('produto_invalido');
    }
}

/**
 * Valida filtro de área em endpoints de leitura (timeline, relatórios).
 *
 * @throws InvalidArgumentException
 */
function caderno_validar_area_filtro_usuario(mysqli $mysqli, int $user_id, int $area_id, ?int $propriedade_id = null): void
{
    if ($area_id <= 0) {
        return;
    }
    caderno_validar_areas_usuario($mysqli, $user_id, [$area_id], $propriedade_id);
}

/**
 * Garante que a estufa pertence à propriedade do usuário.
 *
 * @throws InvalidArgumentException
 */
function caderno_validar_estufa_usuario(mysqli $mysqli, int $user_id, int $estufa_id, int $propriedade_id): void
{
    if ($estufa_id <= 0) {
        throw new InvalidArgumentException('estufa_invalida');
    }

    $stmt = $mysqli->prepare("
        SELECT e.id
        FROM estufas e
        INNER JOIN propriedades p ON p.id = e.propriedade_id
        WHERE e.id = ? AND e.propriedade_id = ? AND p.user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('iii', $estufa_id, $propriedade_id, $user_id);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ok) {
        throw new InvalidArgumentException('estufa_invalida');
    }
}
