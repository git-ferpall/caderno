<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/*
 * 🔒 Garante login:
 * - se não estiver logado → redirect
 * - se estiver logado → retorna JWT (claims)
 */
$user = require_login();

/* 👤 ID do usuário autenticado */
$user_id = (int) $user->sub;

/* ==========================
 * 📥 DADOS DO FORMULÁRIO
 * ========================== */
$id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$titulo    = trim($_POST['titulo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$publico   = isset($_POST['publico']) ? 1 : 0;

/* Itens */
$item_desc = $_POST['item_desc'] ?? [];          // descrições (array indexado)
$item_obs  = $_POST['item_obs']  ?? [];          // checkboxes indexados

/* 🔒 Regra:
 * - modelo padrão → criado_por = 0
 * - modelo pessoal → criado_por = user_id
 */
$criado_por = $publico ? 0 : (int)$user_id;

/* 🚫 Validação mínima */
if ($titulo === '') {
    die('Título é obrigatório');
}

/* ==========================
 * 💾 SALVAR MODELO
 * ========================== */
if ($id > 0) {

    /* 🔎 Segurança: só edita modelo próprio */
    $stmt = $mysqli->prepare("
        SELECT criado_por, publico
        FROM checklist_modelos
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $modelo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$modelo) {
        die('Modelo não encontrado');
    }

    if ((int)$modelo['publico'] === 1) {
        die('Modelos padrão não podem ser alterados');
    }

    if ((int)$modelo['criado_por'] !== (int)$user_id) {
        http_response_code(403);
        die('Sem permissão para editar este modelo');
    }

    /* UPDATE do modelo */
    $stmt = $mysqli->prepare("
        UPDATE checklist_modelos
        SET
            titulo = ?,
            descricao = ?,
            publico = ?,
            criado_por = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "ssiii",
        $titulo,
        $descricao,
        $publico,
        $criado_por,
        $id
    );
    $stmt->execute();
    $stmt->close();

    /* Remove itens antigos */
    $stmt = $mysqli->prepare("
        DELETE FROM checklist_modelo_itens
        WHERE modelo_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

} else {

    /* INSERT do modelo */
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
    $id = $stmt->insert_id;
    $stmt->close();
}

/* ==========================
 * 💾 SALVAR ITENS (ORDEM + OBS)
 * ========================== */
$ordem = 1;

$stmt = $mysqli->prepare("
    INSERT INTO checklist_modelo_itens
        (modelo_id, descricao, permite_observacao, ordem)
    VALUES (?, ?, ?, ?)
");

foreach ($item_desc as $idx => $desc) {
    $desc = trim($desc);
    if ($desc === '') continue;

    /* checkbox indexado:
     * - se NÃO veio no POST → desmarcado (0)
     * - se veio → marcado (1)
     */
    $permite_obs = isset($item_obs[$idx]) ? 1 : 0;

    $stmt->bind_param(
        "isii",
        $id,
        $desc,
        $permite_obs,
        $ordem
    );
    $stmt->execute();
    $ordem++;
}

$stmt->close();

/* ==========================
 * 🔁 REDIRECIONA
 * ========================== */
header('Location: index.php');
exit;