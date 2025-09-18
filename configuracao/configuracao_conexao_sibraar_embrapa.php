<?php

if(!defined("HOST_SIBRAAR_EMBRAPA")) {
    define("HOST_SIBRAAR_EMBRAPA", getenv('DB_SIBRAAR_EMBRAPA_HOST'));
}
if (!defined("USER_SIBRAAR_EMBRAPA")) {
    define("USER_SIBRAAR_EMBRAPA", getenv('DB_SIBRAAR_EMBRAPA_USER'));
}
if (!defined("PASSWORD_SIBRAAR_EMBRAPA")) {
    define("PASSWORD_SIBRAAR_EMBRAPA", getenv('DB_SIBRAAR_EMBRAPA_PASSWORD'));
}
if (!defined("DATABASE_SIBRAAR_EMBRAPA")) {
    define("DATABASE_SIBRAAR_EMBRAPA", getenv('DB_SIBRAAR_EMBRAPA_NAME'));
}

$mysqli_sibraar_embrapa = new mysqli(HOST_SIBRAAR_EMBRAPA, USER_SIBRAAR_EMBRAPA, PASSWORD_SIBRAAR_EMBRAPA, DATABASE_SIBRAAR_EMBRAPA);
$mysqli_sibraar_embrapa->set_charset("utf8");