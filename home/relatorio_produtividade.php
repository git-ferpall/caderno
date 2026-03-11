<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Caderno de Campo - Frutag</title>

<link rel="stylesheet" href="../css/style.css">
<link rel="icon" type="image/png" href="/img/logo-icon.png">

<style>

#pf-propriedade{
    height:auto;
    min-height:48px;
    max-height:140px;
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#f8f8f8;
    font-size:15px;
}

.spinner{
    width:50px;
    height:50px;
    border:5px solid #ddd;
    border-top:5px solid #4caf50;
    border-radius:50%;
    animation:spin 1s linear infinite;
    margin:auto;
}

@keyframes spin{
    100%{ transform:rotate(360deg); }
}

</style>

</head>

<body>

<div id="loading" style="display:none;"></div>
<div id="loading-screen" style="display:none;"></div>

<?php include '../include/loading.php' ?>
<?php include '../include/popups.php' ?>

<!-- OVERLAY GERANDO RELATÓRIO -->

<div id="loading-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(255,255,255,0.8);z-index:9999;align-items:center;justify-content:center;font-family:sans-serif;">

<div style="text-align:center">

<div class="spinner"></div>

<p style="margin-top:10px;font-weight:bold;color:#2e7d32">
Gerando relatório, aguarde...
</p>

</div>

</div>

<div id="conteudo">

<?php include '../include/menu.php' ?>

<?php

date_default_timezone_set("America/Sao_Paulo");

$dt_ini = date("Y-m-01");
$dt_fin = date("Y-m-t");

?>

<main id="relatorios" class="sistema">

<div class="page-title">
<h2 class="main-title cor-branco">Relatório de Safra</h2>
</div>

<div class="sistema-main">

    <form
        id="rel-form"
        action="../funcoes/relatorios/relatorio_safra_pdf.php"
        method="POST"
        target="_blank"
        class="main-form container"
    >

    <!-- PROPRIEDADE -->

    <div class="form-campo">

    <label>Propriedade</label>

    <select
        name="propriedade"
        id="pf-propriedade"
        class="form-select form-text"
        required
    >

    <option value="" disabled selected>Selecione a propriedade</option>

    </select>

    </div>

    <!-- AREA -->

    <div class="form-campo">

    <label>Área</label>

    <select
        name="area"
        id="pf-area"
        class="form-select form-text"
        required
    >

    <option value="" disabled selected>Selecione a área</option>

    </select>

    </div>

    <!-- PRODUTO -->

    <div class="form-campo">

    <label>Produto</label>

    <select
        name="produto"
        id="pf-produto"
        class="form-select form-text"
        required
    >

    <option value="" disabled selected>Selecione um produto</option>

    </select>

    </div>

    <!-- DATA INICIAL -->

    <div class="form-campo">

    <label>Data inicial</label>

    <input
        type="date"
        name="data_ini"
        value="<?php echo $dt_ini ?>"
        class="form-text"
    >

    </div>

    <!-- DATA FINAL -->

    <div class="form-campo">

    <label>Data final</label>

    <input
        type="date"
        name="data_fim"
        value="<?php echo $dt_fin ?>"
        class="form-text"
    >

    </div>

    <!-- BOTÃO -->

    <div class="form-submit">

    <button
        id="form-pdf-relatorio"
        class="main-btn fundo-laranja"
        type="submit"
    >
    Gerar relatório de safra
    </button>

    </div>

</form>
</div>

</main>

<?php include '../include/imports.php' ?>

<script src="../js/relatorio_produtividade.js"></script>

</div>

<!-- SELECT2 -->

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>

/* SELECT2 */

document.addEventListener("DOMContentLoaded", function(){

$('#pf-propriedade').select2({
placeholder: "Selecione uma propriedade",
width: "100%",
language: "pt-BR"
});

});

/* GERAR RELATÓRIO */


document.getElementById("form-pdf-relatorio").addEventListener("click", function(){

const overlay = document.getElementById("loading-overlay");

overlay.style.display = "flex";

document.getElementById("rel-form").submit();

/* esconder spinner depois */

setTimeout(function(){
overlay.style.display = "none";
},2000);

});

</script>

<?php include '../include/footer.php' ?>

</body>
</html>