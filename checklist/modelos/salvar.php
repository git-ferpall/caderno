<?php
/**
 * Salvar MODELO de checklist
 * - Criação e edição
 * - Permissões corretas (público / privado)
 * - Obs / Foto / Doc
 * - Ordem dos itens
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Login */
$user = require_login();
$user_id = (int)$user->sub;

$mysqli->begin_transaction();

try {

    /* ======================
     * IDENTIFICA SE É EDIÇÃO OU CRIAÇÃO
     * ====================== */
    $modelo_id = (
        isset($_POST['id']) &&
        is_numeric($_POST['id']) &&
        (int)$_POST['id'] > 0
    ) ? (int)$_POST['id'] : 0;

    /* ======================
     * DADOS BÁSICOS
     * ====================== */
    $titulo    = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $publico   = isset($_POST['publico']) ? 1 : 0;

    if ($titulo === '') {
        throw new Exception('Título obrigatório');
    }

    /* ======================
     * EDIÇÃO
     * ====================== */
    if ($modelo_id > 0) {

        // 🔎 Busca modelo e valida permissão
        $stmt = $mysqli->prepare("
            SELECT id, publico, criado_por
            FROM checklist_modelos
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $modelo_id);
        $stmt->execute();
        $modelo = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (
            !$modelo ||
            (
                (int)$modelo['publico'] === 0 &&
                (int)$modelo['criado_por'] !== $user_id
            )
        ) {
            throw new Exception('Modelo não existe ou sem permissão');
        }

        // Atualiza modelo
        $stmt = $mysqli->prepare("
            UPDATE checklist_modelos
            SET titulo = ?, descricao = ?, publico = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssii", $titulo, $descricao, $publico, $modelo_id);
        $stmt->execute();
        $stmt->close();

        // Remove itens antigos
        $stmt = $mysqli->prepare("
            DELETE FROM checklist_modelo_itens
            WHERE modelo_id = ?
        ");
        $stmt->bind_param("i", $modelo_id);
        $stmt->execute();
        $stmt->close();

    }
    /* ======================
     * CRIAÇÃO
     * ====================== */
    else {

        $stmt = $mysqli->prepare("
            INSERT INTO checklist_modelos
                (titulo, descricao, publico, criado_por)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssii", $titulo, $descricao, $publico, $user_id);
        $stmt->execute();

        $modelo_id = (int)$stmt->insert_id;
        $stmt->close();

        if ($modelo_id <= 0) {
            throw new Exception('Falha ao criar modelo');
        }
    }

    /* ======================
     * ITENS DO MODELO
     * ====================== */
    $item_keys  = $_POST['item_key']  ?? [];
    $item_desc  = $_POST['item_desc'] ?? [];
    $item_obs   = $_POST['item_obs']  ?? [];
    $item_foto  = $_POST['item_foto'] ?? [];
    $item_anexo = $_POST['item_anexo'] ?? [];

    $ordem = 1;

    $stmt = $mysqli->prepare("
        INSERT INTO checklist_modelo_itens
            (modelo_id, descricao, permite_observacao, permite_foto, permite_anexo, ordem)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($item_keys as $key) {

        $desc = trim($item_desc[$key] ?? '');
        if ($desc === '') continue;

        $obs   = isset($item_obs[$key])   ? 1 : 0;
        $foto  = isset($item_foto[$key])  ? 1 : 0;
        $anexo = isset($item_anexo[$key]) ? 1 : 0;

        $stmt->bind_param(
            "isiiii",
            $modelo_id,
            $desc,
            $obs,
            $foto,
            $anexo,
            $ordem
        );
        $stmt->execute();
        $ordem++;
    }

    $stmt->close();

    /* ======================
     * FINALIZA
     * ====================== */
    $mysqli->commit();

    header('Location: index.php');
    exit;

} catch (Throwable $e) {

    $mysqli->rollback();
    die('Erro ao salvar modelo: ' . $e->getMessage());
}
