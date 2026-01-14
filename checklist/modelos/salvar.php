<?php
/**
 * Salvar (Criar / Editar) MODELO de checklist
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

$mysqli->begin_transaction();

try {

    /* ======================
     * IDENTIFICA AÇÃO
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

        $stmt = $mysqli->prepare("
            SELECT id
            FROM checklist_modelos
            WHERE id = ?
              AND criado_por = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $modelo_id, $user_id);
        $stmt->execute();
        $existe = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$existe) {
            throw new Exception('Modelo não existe ou sem permissão');
        }

        $stmt = $mysqli->prepare("
            UPDATE checklist_modelos
            SET titulo = ?, descricao = ?, publico = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssii", $titulo, $descricao, $publico, $modelo_id);
        $stmt->execute();
        $stmt->close();

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
        var_dump([
        'POST' => $_POST,
        'modelo_id_calculado' => $modelo_id,
        'user_id' => $user_id
    ]);
    exit;
    /* ======================
     * ITENS
     * ====================== */
    $item_keys = $_POST['item_key'] ?? [];
    $item_desc = $_POST['item_desc'] ?? [];
    $item_obs  = $_POST['item_obs']  ?? [];
    $item_foto = $_POST['item_foto'] ?? [];

    $ordem = 1;

    $stmt = $mysqli->prepare("
        INSERT INTO checklist_modelo_itens
            (modelo_id, descricao, permite_observacao, permite_foto, ordem)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($item_keys as $key) {

        $desc = trim($item_desc[$key] ?? '');
        if ($desc === '') continue;

        $obs  = isset($item_obs[$key])  ? 1 : 0;
        $foto = isset($item_foto[$key]) ? 1 : 0;

        $stmt->bind_param(
            "isiii",
            $modelo_id,
            $desc,
            $obs,
            $foto,
            $ordem
        );
        $stmt->execute();
        $ordem++;
    }

    $stmt->close();

    $mysqli->commit();
    header('Location: index.php');
    exit;

} catch (Throwable $e) {
    $mysqli->rollback();
    die('Erro ao salvar modelo: ' . $e->getMessage());
}
