<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

/* 📥 LÊ JSON DO FETCH */
$dados = json_decode(file_get_contents('php://input'), true);

$checklist_id = (int)($dados['checklist_id'] ?? 0);
$assinatura_b64 = $dados['imagem'] ?? null;

if (!$checklist_id || !$assinatura_b64) {
    echo json_encode(['ok' => false, 'erro' => 'Dados inválidos']);
    exit;
}

/* 🔎 Checklist válido e aberto */
$stmt = $mysqli->prepare("
    SELECT id
    FROM checklists
    WHERE id = ? AND user_id = ? AND concluido = 0
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$chk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$chk) {
    echo json_encode(['ok' => false, 'erro' => 'Checklist inválido ou já finalizado']);
    exit;
}

/* 🖊️ SALVA ASSINATURA */
$img = str_replace('data:image/png;base64,', '', $assinatura_b64);
$img = base64_decode($img);

$assinatura_nome = 'assinatura_' . uniqid() . '.png';
$dir = __DIR__ . "/../../uploads/checklists/$checklist_id/assinatura";

if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

file_put_contents("$dir/$assinatura_nome", $img);

/* 🔐 HASH */
$hash_base = [
    'checklist_id' => $checklist_id,
    'assinatura' => $assinatura_nome,
    'fechado_em' => date('Y-m-d H:i:s')
];

$hash = hash('sha256', json_encode($hash_base, JSON_UNESCAPED_UNICODE));

/* 🔒 FINALIZA CHECKLIST */
$stmt = $mysqli->prepare("
    UPDATE checklists
    SET
        assinatura_arquivo = ?,
        hash_documento = ?,
        fechado_em = NOW(),
        concluido = 1
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ssii", $assinatura_nome, $hash, $checklist_id, $user_id);
$stmt->execute();
$stmt->close();

/* ✅ OK */
echo json_encode(['ok' => true]);
exit;
