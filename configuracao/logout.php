<?php
require_once __DIR__.'/../configuracao/env.php';
$params = [
  'expires'  => time()-3600,
  'path'     => '/',
  'secure'   => isset($_SERVER['HTTPS']),
  'httponly' => true,
  'samesite' => 'Lax',
];
setcookie(AUTH_COOKIE, '', $params);
header('Location: /login.php');
exit;
