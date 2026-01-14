<?php
/**
 * Salvar (Criar / Editar) MODELO de checklist
 * Stack: MySQLi + protect.php
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';
$_POST['modelo_id'] ✅ É O CERTO
exit;

/* 🔒 Login obrigatório */
$user = require_login();
$user_id = (int) $user->sub;

$mysqli->begin_transaction();

try {

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
     * IDENTIFICA EDIÇÃO OU CRIAÇÃO
     * ====================== */
    $modelo_id = (int)($_POST['modelo_id'] ?? 0);

    $is_edicao = false;

    if ($modelo_id > 0) {

        // 🔐 Confirma que o modelo EXISTE e pertence ao usuário
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

        if ($existe) {
            $is_edicao = true;
        }
    }

    /* ======================
     * SALVA MODELO
     * ====================== */
    if ($is_edicao) {

        // ✏️ UPDATE
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

    } else {

        // ➕ INSERT (CRIAÇÃO)
        $stmt = $mysqli->prepare("
            INSERT INTO checklist_modelos
                (titulo, descricao, publico, criado_por)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssii", $titulo, $descricao, $publico, $user_id);
        $stmt->execute();

        $modelo_id = (int) $stmt->insert_id;
        $stmt->close();

        if ($modelo_id <= 0) {
            throw new Exception('Falha ao criar modelo');
        }
    }

    /* ======================
     * ITENS DO CHECKLIST
     * ====================== */
    $item_keys  = $_POST['item_key']   ?? [];
    $item_desc  = $_POST['item_desc']  ?? [];
    $item_obs   = $_POST['item_obs']   ?? [];
    $item_foto  = $_POST['item_foto']  ?? [];
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

        $permite_obs   = isset($item_obs[$key])   ? 1 : 0;
        $permite_foto  = isset($item_foto[$key])  ? 1 : 0;
        $permite_anexo = isset($item_anexo[$key]) ? 1 : 0;

        $stmt->bind_param(
            "isiiii",
            $modelo_id,
            $desc,
            $permite_obs,
            $permite_foto,
            $permite_anexo,
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
