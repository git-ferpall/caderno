<?php
declare(strict_types=1);

// inicia buffer de saída para evitar "headers already sent"
ob_start();

// carrega o middleware de autenticação (JWT)
require_once __DIR__ . '/auth.php';

// força login → se não estiver autenticado, redireciona para index.php
$user = require_login();

// $user agora contém as claims do JWT
// Ex: $user->sub, $user->name, $user->email

// deixa acessível globalmente
$GLOBALS['auth_user'] = $user;
