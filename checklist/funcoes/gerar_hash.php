<?php
/**
 * Gera hash de integridade de um checklist
 */

function gerarHashChecklist(mysqli $mysqli, int $checklist_id): string
{
    // 🔎 Dados do checklist
    $stmt = $mysqli->prepare("
        SELECT id, user_id, fechado_em
        FROM checklists
        WHERE id = ? AND concluido = 1
        LIMIT 1
    ");
    $stmt->bind_param("i", $checklist_id);
    $stmt->execute();
    $checklist = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$checklist) {
        throw new Exception('Checklist não finalizado');
    }

    // 🔎 Itens
    $stmt = $mysqli->prepare("
        SELECT
            id,
            concluido,
            observacao,
            ordem
        FROM checklist_itens
        WHERE checklist_id = ?
        ORDER BY ordem
    ");
    $stmt->bind_param("i", $checklist_id);
    $stmt->execute();
    $itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 🧱 Estrutura canônica
    $payload = [
        'checklist_id' => $checklist['id'],
        'user_id'      => $checklist['user_id'],
        'fechado_em'   => $checklist['fechado_em'],
        'itens'        => $itens
    ];

    // 🔐 Hash SHA-256
    return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
}
