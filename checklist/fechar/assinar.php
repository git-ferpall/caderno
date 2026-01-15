<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

$checklist_id = (int)($_GET['id'] ?? 0);
if (!$checklist_id) die('Checklist inválido');
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Assinatura do Checklist</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
canvas {
    border: 2px dashed #999;
    width: 100%;
    height: 220px;
}
</style>
</head>

<body class="bg-light">
<div class="container py-4">

<h4>✍️ Assine para finalizar o checklist</h4>

<canvas id="canvas"></canvas>

<div class="mt-3">
    <button class="btn btn-secondary" onclick="limpar()">Limpar</button>
    <button class="btn btn-success" onclick="salvar()">Salvar assinatura</button>
</div>

</div>

<script>
const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');
canvas.width = canvas.offsetWidth;
canvas.height = 220;

let desenhando = false;

canvas.addEventListener('mousedown', () => desenhando = true);
canvas.addEventListener('mouseup', () => desenhando = false);
canvas.addEventListener('mousemove', desenhar);

canvas.addEventListener('touchstart', () => desenhando = true);
canvas.addEventListener('touchend', () => desenhando = false);
canvas.addEventListener('touchmove', desenhar);

function desenhar(e) {
    if (!desenhando) return;
    const rect = canvas.getBoundingClientRect();
    const x = (e.clientX || e.touches[0].clientX) - rect.left;
    const y = (e.clientY || e.touches[0].clientY) - rect.top;
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#000';
    ctx.lineTo(x, y);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(x, y);
}

function limpar() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

function salvar() {
    const data = canvas.toDataURL('image/png');

    fetch('salvar_assinatura.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            checklist_id: <?= $checklist_id ?>,
            imagem: data
        })
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.ok) {
            window.location.href = '../fechar/processar.php?id=<?= $checklist_id ?>';
        } else {
            alert(resp.erro);
        }
    });
}
</script>

</body>
</html>
