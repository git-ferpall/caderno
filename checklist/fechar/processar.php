<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
session_start();

$checklist_id = (int)$_POST['checklist_id'];

$gps_lat = $_POST['gps_lat'] ?? null;
$gps_lng = $_POST['gps_lng'] ?? null;
$gps_acc = $_POST['gps_acc'] ?? null;

// 🔎 Busca checklist
$stmt = $pdo->prepare("SELECT * FROM checklists WHERE id = ?");
$stmt->execute([$checklist_id]);
$chk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chk || $chk['hash_documento']) die('Checklist inválido');

/* 🖊️ Assinatura */
$assinatura_nome = null;
if (!empty($_POST['assinatura'])) {
  $data = base64_decode(str_replace('data:image/png;base64,','',$_POST['assinatura']));
  $assinatura_nome = 'assinatura_'.uniqid().'.png';

  $dir = __DIR__."/../../uploads/checklists/$checklist_id/assinatura";
  if (!is_dir($dir)) mkdir($dir,0755,true);

  file_put_contents("$dir/$assinatura_nome",$data);
}

/* 🔐 Hash */
$base_hash = [
  'checklist_id' => $checklist_id,
  'gps' => [
    'lat'=>$gps_lat,
    'lng'=>$gps_lng,
    'acc'=>$gps_acc
  ],
  'assinatura' => $assinatura_nome,
  'fechado_em' => date('Y-m-d H:i:s')
];

$hash = hash('sha256', json_encode($base_hash));

/* 💾 Salva */
$pdo->prepare("
UPDATE checklists SET
  gps_lat=?,
  gps_lng=?,
  gps_accuracy=?,
  assinatura_arquivo=?,
  hash_documento=?,
  fechado_em=NOW(),
  concluido=1
WHERE id=?
")->execute([
  $gps_lat,$gps_lng,$gps_acc,
  $assinatura_nome,$hash,$checklist_id
]);

header("Location: ../pdf/gerar.php?id=$checklist_id");
