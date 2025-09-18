<?php

if (in_array($_SERVER['HTTP_HOST'], array('localhost', '127.0.0.1'))) {
    if (!defined("HOST")) {
        define("HOST", getenv('DB_HOST'));
    }
    if (!defined("USER")) {
        define("USER", getenv('DB_USER'));
    }
    if (!defined("PASSWORD")) {
        define("PASSWORD", getenv('DB_PASSWORD'));
    }
    if (!defined("DATABASE")) {
        define("DATABASE", getenv('DB_NAME'));
    }
    if (!defined("CAMINHO_BASE")) {
        define("CAMINHO_BASE", realpath($_SERVER["DOCUMENT_ROOT"]) . '/sibraar/sistema/login');
    }
    $ambiente_desenvolvimento = true;
} else {
    if (!defined("HOST")) {
        define("HOST", getenv('DB_HOST'));
    }
    if (!defined("USER")) {
        define("USER", getenv('DB_USER'));
    }
    if (!defined("PASSWORD")) {
        define("PASSWORD", getenv('DB_PASSWORD'));
    }
    if (!defined("DATABASE")) {
        define("DATABASE", getenv('DB_NAME'));
    }
    if (!defined("CAMINHO_BASE")) {
        define("CAMINHO_BASE", realpath($_SERVER["DOCUMENT_ROOT"]) . "/login");
    }
    $ambiente_desenvolvimento = false;
}

$mysqli = new mysqli(HOST, USER, PASSWORD, DATABASE);
$mysqli->set_charset("utf8");

setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');