<?php
/**
 * Cria um CHECKLIST (instância) a partir de um MODELO
 * Stack: MySQLi + protect.php (SSO)
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Garante login */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Modelo selecionado */
$modelo_id = isset($_POST['modelo_id']) ? (int)$_POST['modelo_id'] : 0;

if (!$modelo_id) {
    die('Modelo inválido');
}

/* 🔎 Verifica se modelo existe e se é acessível */
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
    die('Modelo não encontrado');
}

/* 🔒 Segurança:
 * - modelo público → ok
 * - modelo pessoal → só o dono pode usar
 */
if ((int)$modelo['publico'] === 0 && (int)$modelo['criado_por'] !== (int)$user_id) {
    http_response_code(403);
    die('Você não tem permissão para usar este modelo');
}

/* ==========================
 * 🧾 CRIA CHECKLIST
 * ========================== */
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
$checklist_id = $stmt->insert_id;
$stmt->close();

/* ==========================
 * 📋 COPIA ITENS DO MODELO
 * ========================== */
$stmt = $mysqli->prepare("
    INSERT INTO checklist_itens
        (checklist_id, descricao, permite_observacao, ordem)
    SELECT
        ?, descricao, permite_observacao, ordem
    FROM checklist_modelo_itens
    WHERE modelo_id = ?
    ORDER BY ordem
");
$stmt->bind_param("ii", $checklist_id, $modelo_id);
$stmt->execute();
$stmt->close();

/* ==========================
 * ➡️ REDIRECIONA PARA PREENCHER
 * ========================== */
header("Location: ../preencher/index.php?id={$checklist_id}");
exit;
