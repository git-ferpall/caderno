<?php

$protocol = 'https://'; // Força sempre HTTPS

if ($_SERVER["SERVER_NAME"] == "localhost") {
  $site_url_rastro            = $protocol . "localhost/frutag.app.br/id";
  $configuracao_site_url_raiz = $protocol . "localhost/frutag.app.br";
  $configuracao_site_url      = $protocol . "localhost/frutag.app.br/login";
  $configuracao_site_nome     = "frutag.app.br";
} else {
  $site_url_rastro            = $protocol . "frutag.app.br/id";
  $configuracao_site_url_raiz = $protocol . "frutag.app.br";
  $configuracao_site_url      = $protocol . "frutag.app.br/login";
  $configuracao_site_nome     = "frutag.app.br";
}