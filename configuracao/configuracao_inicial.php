<?php

require_once "configuracao_conexao.php";
require_once "configuracao_funcoes.php";

sec_session_start();

if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
  $protocol = 'https://';
} else {
  $protocol = 'http://';
}

if ($_SERVER["SERVER_NAME"] == "localhost") {
    $site_url_rastro  = $protocol."frutag.app.br/id";
    $site_url         = $protocol."frutag.app.br/login";
    $site_nome        = "frutag.app.br";
    $caminho_arquivos = $protocol."localhost/frutag.app.br/login";
    $caminho_usuarios = $protocol."localhost/frutag.app.br/login/clientes/" . $_SESSION["cliente_cod"] . "/";
} else {
    $site_url_rastro  = $protocol."frutag.app.br/id";
    $site_url         = $protocol."frutag.app.br/login";
    $site_nome        = "frutag.app.br";
    $caminho_arquivos = $protocol."frutag.app.br/login";
    $caminho_usuarios = $protocol."frutag.app.br/login/clientes/" . $_SESSION["cliente_cod"] . "/";
}

define("CAMINHO_ARQUIVOS", $caminho_arquivos);
define("CAMINHO_USUARIOS", $caminho_usuarios);