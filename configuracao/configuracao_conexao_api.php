<?php

$servername_api           = getenv('DB_HOST');
$username_api             = getenv('DB_USER');
$password_api             = getenv('DB_PASSWORD');
$database_api_homologacao = getenv('DB_NAME_API_HOMOLOGACAO');
$database_api_producao    = getenv('DB_NAME_API_PRODUCAO');

$mysqli_api_homologacao = new mysqli($servername_api, $username_api, $password_api, $database_api_homologacao);
$mysqli_api_homologacao->set_charset("utf8");

$mysqli_api_producao = new mysqli($servername_api, $username_api, $password_api, $database_api_producao);
$mysqli_api_producao->set_charset("utf8");
