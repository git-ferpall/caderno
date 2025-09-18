<?php
require_once __DIR__ . "/env.php";
require_once __DIR__ . "/http.php";

$login   = trim($_POST["login"] ?? "");
$senha   = trim($_POST["senha"] ?? "");
$next    = $_POST["next"] ?? "/";
$captcha = $_POST["g-recaptcha-response"] ?? "";

if ($login === "" || $senha === "") { header("Location: /login.php?e=1"); exit; }

$r = http_post_form(AUTH_API_LOGIN, [
  "login" => $login, "senha" => $senha, "captcha" => $captcha
]);

if ($r['status'] !== 200 || !$r['body']) {
  header("Location: /login.php?e=api"); exit;   // API fora/500/404
}

$data = json_decode($r['body'], true);
if (!is_array($data) || empty($data["ok"]) || empty($data["token"])) {
  header("Location: /login.php?e=2"); exit;
}

/* TESTE sem HTTPS: cookie sem domain/secure */
setcookie(AUTH_COOKIE, $data["token"], [
  "expires"=> time()+3600, "path"=>"/", "httponly"=>true, "samesite"=>"Lax"
]);

header("Location: " . ($next ?: "/")); exit;
