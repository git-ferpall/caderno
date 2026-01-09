<?php
/**
 * Gera hash de integridade do checklist
 * O hash é baseado APENAS nos dados preenchidos
 */

function gerarHashChecklist(mysqli $mysqli, int $checklist_id): string
{
    /* 🔎 Verifica se checklist existe */
    $stmt = $mysqli->prepare("
        SELECT id
        FROM checklists
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $checklist_id);
    $stmt->execute();
    $chk = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$chk) {
        throw new Exception('Checklist não encontrado');
    }

    /* 🔎 Itens */
    $stmt = $mysqli->prepare("
        SELECT
            descricao,
            concluido,
            COALESCE(observacao, '')
        FROM checklist_itens
        WHERE checklist_id = ?
        ORDER BY ordem
    ");
    $stmt->bind_param("i", $checklist_id);
    $stmt->execute();
    $itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!$itens) {
        throw new Exception('Checklist sem itens');
    }

    /* 🔐 Base do hash */
    $base = json_encode($itens, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return hash('sha256', $base);
}
