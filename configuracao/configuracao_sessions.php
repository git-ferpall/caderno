<?php

require_once "configuracao_inicial.php";

$logged = isLogged();

if ($logged == false) {
    session_destroy();
    header("location: " . CAMINHO_ARQUIVOS . "/");
    die();
}