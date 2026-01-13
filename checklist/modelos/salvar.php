<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

$id        = (int)($_POST['id'] ?? 0);
$titulo    = trim($_POST['titulo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$publico   = isset($_POST['publico']) ? 1 : 0;

$item_desc   = $_POST['item_desc']  ?? [];
$item_obs    = $_POST['item_obs']   ?? [];
$item_foto   = $_POST['item_foto']  ?? [];
$item_anexo  = $_POST['item_anexo'] ?? [];

if ($titulo === '') {
    die('Título obrigatório');
}

$mysqli->begin_transaction();

try {

    /* ================= MODELO ================= */

    if ($id > 0) {

        // 🔒 NÃO sobrescreve criado_por em edição
        $stmt = $mysqli->prepare("
            UPDATE checklist_modelos
            SET titulo = ?, descricao = ?, publico = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssii", $titulo, $descricao, $publico, $id);
        $stmt->execute();
        $stmt->close();

    } else {

        $criado_por = $publico ? 0 : $user_id;

        $stmt = $mysqli->prepare("
            INSERT INTO checklist_modelos
                (titulo, descricao, publico, criado_por, criado_em, ativo)
            VALUES (?, ?, ?, ?, NOW(), 1)
        ");
        $stmt->bind_param("ssii", $titulo, $descricao, $publico, $criado_por);
        $stmt->execute();

        $id = $stmt->insert_id;
        $stmt->close();
    }

    if (!$id) {
        throw new Exception('ID do modelo inválido');
    }

    /* ================= ITENS ================= */

    $stmt = $mysqli->prepare("
        DELETE FROM checklist_modelo_itens
        WHERE modelo_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("
        INSERT INTO checklist_modelo_itens
            (modelo_id, descricao, permite_observacao, permite_foto, permite_anexo, ordem)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $ordem = 1;

    foreach ($item_desc as $key => $desc) {

        $desc = trim($desc);
        if ($desc === '') continue;

        $obs    = isset($item_obs[$key])   ? 1 : 0;
        $foto   = isset($item_foto[$key])  ? 1 : 0;
        $anexo  = isset($item_anexo[$key]) ? 1 : 0;

        // 🔒 Exclusividade backend
        if ($foto) {
            $obs = $anexo = 0;
        } elseif ($anexo) {
            $obs = $foto = 0;
        }

        $stmt->bind_param(
            "isiiii",
            $id,
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

    $mysqli->commit();

    header('Location: index.php');
    exit;

} catch (Throwable $e) {

    $mysqli->rollback();
    die('Erro ao salvar modelo: ' . $e->getMessage());
}
