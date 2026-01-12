<?php
function gerarHashChecklist(mysqli $mysqli, int $checklist_id): string
{
    /* ✅ Checklist (somente finalizado) */
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

    /* ✅ Itens */
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

    /* ✅ Arquivos + HASH DO CONTEÚDO */
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
    $arquivos_db = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $arquivos = [];

    foreach ($arquivos_db as $a) {
        $path = __DIR__ . "/../../uploads/checklists/$checklist_id/item_{$a['checklist_item_id']}/{$a['arquivo']}";

        $arquivos[] = [
            'item'   => $a['checklist_item_id'],
            'tipo'   => $a['tipo'],
            'arquivo'=> $a['arquivo'],
            'hash'   => file_exists($path) ? hash_file('sha256', $path) : null
        ];
    }

    /* ✅ Assinatura digital (entra no hash) */
    $stmt = $mysqli->prepare("
        SELECT arquivo
        FROM checklist_assinaturas
        WHERE checklist_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $checklist_id);
    $stmt->execute();
    $ass = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $assinatura_hash = null;

    if ($ass) {
        $path = __DIR__ . "/../../uploads/checklists/$checklist_id/{$ass['arquivo']}";
        if (file_exists($path)) {
            $assinatura_hash = hash_file('sha256', $path);
        }
    }

    /* ✅ Payload final */
    $payload = [
        'checklist'  => $chk,
        'itens'      => $itens,
        'arquivos'   => $arquivos,
        'assinatura' => $assinatura_hash
    ];

    return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
}
