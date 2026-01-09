<?php
require_once '../../config/db.php';
session_start();

$checklist_id = $_POST['checklist_id'];

// 🔒 Verifica se já está fechado
$stmt = $pdo->prepare("
    SELECT hash_documento
    FROM checklists
    WHERE id = ?
");
$stmt->execute([$checklist_id]);
$chk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chk) {
    die('Checklist não encontrado');
}

if ($chk['hash_documento']) {
    die('Checklist já foi fechado');
}

/* ===============================
   1️⃣ Coletar dados do checklist
   =============================== */

// Itens + anexos
$sql = "
SELECT 
    i.id,
    i.descricao,
    i.concluido,
    i.observacao,
    i.data_conclusao,
    a.arquivo,
    a.tipo
FROM checklist_itens i
LEFT JOIN checklist_item_anexos a
    ON a.checklist_item_id = i.id
WHERE i.checklist_id = ?
ORDER BY i.id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$checklist_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estrutura organizada para hash
$base_hash = [
    'checklist_id' => (int)$checklist_id,
    'fechado_em'   => date('Y-m-d H:i:s'),
    'itens'        => []
];

foreach ($rows as $r) {
    $item_id = $r['id'];

    if (!isset($base_hash['itens'][$item_id])) {
        $base_hash['itens'][$item_id] = [
            'descricao' => trim($r['descricao']),
            'concluido' => (int)$r['concluido'],
            'observacao'=> trim((string)$r['observacao']),
            'data'      => $r['data_conclusao'],
            'anexos'    => []
        ];
    }

    if ($r['arquivo']) {
        $base_hash['itens'][$item_id]['anexos'][] = [
            'tipo'    => $r['tipo'],
            'arquivo' => $r['arquivo']
        ];
    }
}

// Normaliza índices
$base_hash['itens'] = array_values($base_hash['itens']);

/* ===============================
   2️⃣ Gerar HASH (SHA-256)
   =============================== */

// 🔐 Opcional: SALT do sistema (recomendado)
// define('APP_SECRET', 'sua-chave-secreta-aqui');

$string_hash = json_encode(
    $base_hash,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

// Se quiser usar SALT:
// $hash = hash('sha256', $string_hash . APP_SECRET);

$hash = hash('sha256', $string_hash);

/* ===============================
   3️⃣ Salvar e travar checklist
   =============================== */

$sql = "
UPDATE checklists
SET 
    hash_documento = ?,
    fechado_em = NOW(),
    concluido = 1
WHERE id = ?
";
$pdo->prepare($sql)->execute([$hash, $checklist_id]);

/* ===============================
   4️⃣ Redirecionar
   =============================== */

header("Location: ../preencher/index.php?id=$checklist_id&fechado=1");
exit;
