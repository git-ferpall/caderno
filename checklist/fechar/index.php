<?php
require_once '../../config/db.php';
session_start();

$checklist_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM checklists WHERE id = ?");
$stmt->execute([$checklist_id]);
$checklist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$checklist) die('Checklist não encontrado');
if ($checklist['hash_documento']) die('Checklist já fechado');
?>

<h3>Fechar Checklist</h3>

<div class="alert alert-warning">
⚠️ Ao fechar, o checklist será bloqueado definitivamente.
</div>

<!-- GPS -->
<button class="btn btn-outline-primary mb-3" onclick="capturarGPS()">
📍 Capturar localização
</button>

<div id="gpsInfo" class="small text-muted mb-3"></div>

<!-- Assinatura -->
<h5>🖊️ Assinatura</h5>

<canvas id="assinatura"
        width="400"
        height="150"
        style="border:1px solid #ccc; touch-action:none"></canvas>

<div class="mt-2">
<button class="btn btn-sm btn-secondary" onclick="limparAssinatura()">Limpar</button>
</div>

<form method="post" action="processar.php"
      onsubmit="return prepararEnvio();">

<input type="hidden" name="checklist_id" value="<?= $checklist_id ?>">
<input type="hidden" name="gps_lat" id="gps_lat">
<input type="hidden" name="gps_lng" id="gps_lng">
<input type="hidden" name="gps_acc" id="gps_acc">
<input type="hidden" name="assinatura" id="assinatura_base64">

<button class="btn btn-danger mt-3">
🔒 Fechar checklist
</button>
</form>

<script>
// 🗺️ GPS
function capturarGPS() {
  navigator.geolocation.getCurrentPosition(pos => {
    gps_lat.value = pos.coords.latitude;
    gps_lng.value = pos.coords.longitude;
    gps_acc.value = pos.coords.accuracy;

    gpsInfo.innerHTML =
      `Lat: ${pos.coords.latitude}<br>
       Lng: ${pos.coords.longitude}<br>
       Precisão: ${pos.coords.accuracy} m`;
  }, () => alert('Erro ao obter localização'), {
    enableHighAccuracy:true, timeout:10000
  });
}

// 🖊️ Assinatura
const canvas = document.getElementById('assinatura');
const ctx = canvas.getContext('2d');
let draw = false;

canvas.addEventListener('pointerdown', e => {
  draw = true;
  ctx.beginPath();
  ctx.moveTo(e.offsetX, e.offsetY);
});

canvas.addEventListener('pointermove', e => {
  if (!draw) return;
  ctx.lineTo(e.offsetX, e.offsetY);
  ctx.stroke();
});

canvas.addEventListener('pointerup', () => {
  draw = false;
});

function limparAssinatura() {
  ctx.clearRect(0,0,canvas.width,canvas.height);
}

function prepararEnvio() {
  assinatura_base64.value = canvas.toDataURL('image/png');
  return true;
}
</script>
