<?php
/**
 * Upload de foto/documento de item do checklist
 * - 1 arquivo por item
 * - Compressão de imagem
 * - Remove arquivo anterior
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

header('Content-Type: application/json');

/* 🔒 Login */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Dados */
$item_id = (int)($_POST['item_id'] ?? 0);
$tipo    = $_POST['tipo'] ?? '';
$file    = $_FILES['arquivo'] ?? null;

if (!$item_id || !$file || !in_array($tipo, ['foto','documento'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Dados inválidos']);
    exit;
}

/* 🔎 Valida posse */
$stmt = $mysqli->prepare("
    SELECT i.checklist_id
    FROM checklist_itens i
    JOIN checklists c ON c.id = i.checklist_id
    WHERE i.id = ? AND c.user_id = ?
");
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'erro' => 'Sem permissão']);
    exit;
}

$checklist_id = (int)$res['checklist_id'];

/* 📂 Pasta */
$baseDir = __DIR__ . "/../../uploads/checklists/$checklist_id/item_$item_id";
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0775, true);
}

/* 🧹 Remove arquivo antigo (1 por item) */
$stmt = $mysqli->prepare("
    SELECT id, arquivo FROM checklist_item_arquivos
    WHERE checklist_item_id = ? AND tipo = ?
");
$stmt->bind_param("is", $item_id, $tipo);
$stmt->execute();
$antigo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($antigo) {
    $oldPath = $baseDir . '/' . $antigo['arquivo'];
    if (is_file($oldPath)) unlink($oldPath);

    $stmt = $mysqli->prepare("DELETE FROM checklist_item_arquivos WHERE id = ?");
    $stmt->bind_param("i", $antigo['id']);
    $stmt->execute();
    $stmt->close();
}

/* ==========================
 * 📸 FOTO (compressão)
 * ========================== */
if ($tipo === 'foto') {

    $info = getimagesize($file['tmp_name']);
    if (!$info) {
        echo json_encode(['ok' => false, 'erro' => 'Imagem inválida']);
        exit;
    }

    [$w, $h] = $info;
    $max = 1920;

    if ($w > $max || $h > $max) {
        $ratio = min($max / $w, $max / $h);
        $nw = (int)($w * $ratio);
        $nh = (int)($h * $ratio);
    } else {
        $nw = $w;
        $nh = $h;
    }

    switch ($info['mime']) {
        case 'image/jpeg': $src = imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png':  $src = imagecreatefrompng($file['tmp_name']); break;
        case 'image/webp': $src = imagecreatefromwebp($file['tmp_name']); break;
        default:
            echo json_encode(['ok' => false, 'erro' => 'Formato não suportado']);
            exit;
    }

    $dst = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($dst, $src, 0,0,0,0, $nw,$nh, $w,$h);

    $nomeFinal = 'foto_' . time() . '.jpg';
    imagejpeg($dst, $baseDir . '/' . $nomeFinal, 75);

    imagedestroy($src);
    imagedestroy($dst);

} else {

    /* 📄 DOCUMENTO */
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf','doc','docx','xls','xlsx'])) {
        echo json_encode(['ok' => false, 'erro' => 'Documento inválido']);
        exit;
    }

    $nomeFinal = 'documento_' . time() . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $baseDir . '/' . $nomeFinal);
}

/* 💾 Banco */
$stmt = $mysqli->prepare("
    INSERT INTO checklist_item_arquivos
        (checklist_item_id, tipo, arquivo)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iss", $item_id, $tipo, $nomeFinal);
$stmt->execute();
$stmt->close();

echo json_encode([
    'ok'      => true,
    'arquivo' => $nomeFinal,
    'tipo'    => $tipo
]);
