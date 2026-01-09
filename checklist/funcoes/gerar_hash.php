<?php
function gerarHashChecklist(mysqli $mysqli, int $checklist_id): string {

    // Checklist
    $stmt = $mysqli->prepare("
        SELECT id, titulo, user_id, fechado_em
        FROM checklists
        WHERE id = ? AND concluido = 1
    ");
    $stmt->bind_param("i", $checklist_id);
    $stmt->execute();
    $chk = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$chk) {
        throw new Exception('Checklist não finalizado');
    }

    // Itens + observações
    $stmt = $mysqli->prepare("
        SELECT id, descricao, concluido, observacao
        FROM checklist_itens
        WHERE checklist_id = ?
        ORDER BY ordem
    ");
    $stmt->bind_param("i", $checklist_id);
    $stmt->execute();
    $itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Arquivos
    $stmt = $mysqli->prepare("
        SELECT checklist_item_id, tipo, arquivo
        FROM checklist_item_arquivos
        WHERE checklist_item_id IN (
            SELECT id FROM checklist_itens WHERE checklist_id = ?
        )
        ORDER BY checklist_item_id, arquivo
    ");
    $stmt->bind_param("i", $checklist_id);
    $stmt->execute();
    $arquivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $payload = json_encode([
        'checklist' => $chk,
        'itens'     => $itens,
        'arquivos'  => $arquivos
    ], JSON_UNESCAPED_UNICODE);

    return hash('sha256', $payload);
}
