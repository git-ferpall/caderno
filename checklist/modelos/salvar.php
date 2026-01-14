<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Login obrigatório */
$user = require_login();
$user_id = (int)$user->sub;

/* 🔐 Inicia transação */
$mysqli->begin_transaction();

try {

    /* ======================
     * DADOS DO MODELO
     * ====================== */
    $modelo_id = (isset($_POST['id']) && (int)$_POST['id'] > 0)
        ? (int)$_POST['id']
        : 0;

    $titulo    = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $publico   = isset($_POST['publico']) ? 1 : 0;

    if ($titulo === '') {
        throw new Exception('Título obrigatório');
    }

    $criado_por = $publico ? 0 : $user_id;

    /* ======================
     * CRIAR OU EDITAR MODELO
     * ====================== */

    if ($modelo_id > 0) {

        /* 🔐 Verifica permissão e existência */
        $stmt = $mysqli->prepare("
            SELECT id
            FROM checklist_modelos
            WHERE id = ?
              AND (publico = 1 OR criado_por = ?)
            LIMIT 1
        ");
        $stmt->bind_param("ii", $modelo_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();

        if ($res->num_rows === 0) {
            throw new Exception('Modelo não existe ou sem permissão');
        }

        /* ✏️ Atualiza modelo */
        $stmt = $mysqli->prepare("
            UPDATE checklist_modelos
            SET titulo = ?, descricao = ?, publico = ?, criado_por = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssiii",
            $titulo,
            $descricao,
            $publico,
            $criado_por,
            $modelo_id
        );
        $stmt->execute();

        if ($stmt->errno) {
            throw new Exception('Erro ao atualizar modelo');
        }

        $stmt->close();

        /* 🧹 Remove itens antigos */
        $stmt = $mysqli->prepare("
            DELETE FROM checklist_modelo_itens
            WHERE modelo_id = ?
        ");
        $stmt->bind_param("i", $modelo_id);
        $stmt->execute();
        $stmt->close();

    } else {

        /* ➕ Cria novo modelo */
        $stmt = $mysqli->prepare("
            INSERT INTO checklist_modelos
                (titulo, descricao, publico, criado_por)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssii",
            $titulo,
            $descricao,
            $publico,
            $criado_por
        );
        $stmt->execute();

        if ($stmt->errno) {
            throw new Exception('Erro ao criar modelo');
        }

        $modelo_id = (int)$stmt->insert_id;
        $stmt->close();

        if ($modelo_id <= 0) {
            throw new Exception('Falha ao obter ID do modelo');
        }
    }

    /* ======================
     * VALIDA MODELO (FK SAFE)
     * ====================== */
    $stmt = $mysqli->prepare("
        SELECT id
        FROM checklist_modelos
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $modelo_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    if ($res->num_rows === 0) {
        throw new Exception('Modelo não existe para vincular itens');
    }

    /* ======================
     * ITENS DO CHECKLIST
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

        if ($stmt->errno) {
            throw new Exception('Erro ao inserir item do checklist');
        }

        $ordem++;
    }

    $stmt->close();

    /* ✅ COMMIT */
    $mysqli->commit();

    header('Location: index.php');
    exit;

} catch (Throwable $e) {

    $mysqli->rollback();
    die('Erro ao salvar modelo: ' . $e->getMessage());
}
