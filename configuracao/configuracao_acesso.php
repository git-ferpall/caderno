<?php

$sql_ativo = $mysqli->query("SELECT cli_ativo FROM cliente WHERE cli_cod=" . $_SESSION['cliente_cod'] . " ")->fetch_array();

if ($sql_ativo["cli_ativo"] == "N" & !isset($_SESSION['tipo_acesso'])) {
    $ativo_cli="N";
    header("location: " . CAMINHO_ARQUIVOS . "/modulos/configuracao/view/configuracoes.php");
}
