<?php
/**
 * Cria um CHECKLIST (instância) a partir de um MODELO
 * Stack: MySQLi + protect.php (SSO)
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Login */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Modelo */
$modelo_id = (int)($_POST['modelo_id'] ?? 0);
if (!$modelo_id) {
    die('Modelo inválido');
}

$mysqli->begin_transaction();

try {

    /* =========================
     * 🔎 VALIDA MODELO
     * ========================= */
    $stmt = $mysqli->prepare("
        SELECT id, titulo, publico, criado_por
        FROM checklist_modelos
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $modelo_id);
    $stmt->execute();
    $modelo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$modelo) {
        throw new Exception('Modelo não encontrado');
    }

    if ((int)$modelo['publico'] === 0 && (int)$modelo['criado_por'] !== $user_id) {
        throw new Exception('Sem permissão para usar este modelo');
    }

    /* =========================
     * 🧾 CRIA CHECKLIST
     * ========================= */
    $stmt = $mysqli->prepare("
        INSERT INTO checklists
            (modelo_id, user_id, titulo, criado_em)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param(
        "iis",
        $modelo_id,
        $user_id,
        $modelo['titulo']
    );
    $stmt->execute();
    $checklist_id = (int)$stmt->insert_id;
    $stmt->close();

    if (!$checklist_id) {
        throw new Exception('Erro ao criar checklist');
    }

    /* =========================
     * 📋 COPIA ITENS (COM TIPO)
     * ========================= */
    $stmt = $mysqli->prepare("
        INSERT INTO checklist_itens (
            checklist_id,
            descricao,
            tipo,
            opcoes,
            max_selecoes,
            permite_observacao,
            permite_foto,
            permite_anexo,
            ordem
        )
        SELECT
            ?,
            descricao,
            tipo,
            opcoes,
            max_selecoes,
            permite_observacao,
            permite_foto,
            permite_anexo,
            ordem
        FROM checklist_modelo_itens
        WHERE modelo_id = ?
        ORDER BY ordem
    ");
    $stmt->bind_param("ii", $checklist_id, $modelo_id);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();

    /* =========================
     * ➡️ REDIRECIONA
     * ========================= */
    header("Location: ../preencher/index.php?id={$checklist_id}");
    exit;

} catch (Throwable $e) {

    $mysqli->rollback();
    die('Erro ao criar checklist: ' . caderno_erro_msg($e));
}
