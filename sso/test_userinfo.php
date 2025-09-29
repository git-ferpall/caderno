<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$cookieName = 'AUTH_COOKIE'; // mesmo nome que você usa no sistema

// tenta pegar o token do cookie
$jwt = $_COOKIE[$cookieName] ?? null;

// se não tiver cookie, tenta pegar via query string (pra facilitar teste manual)
if (!$jwt && isset($_GET['token'])) {
    $jwt = $_GET['token'];
}

if (!$jwt) {
    echo "<h3>⚠ Nenhum token encontrado (cookie ou ?token=)</h3>";
    exit;
}

$ch = curl_init('https://caderno.frutag.com.br/sso/userinfo.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $jwt],
]);

$resp = curl_exec($ch);
$err  = curl_error($ch);
curl_close($ch);

echo "<h3>Resposta bruta:</h3>";
echo "<pre>" . htmlspecialchars($resp) . "</pre>";

if ($err) {
    echo "<h3>Erro CURL:</h3>";
    echo "<pre>" . htmlspecialchars($err) . "</pre>";
    exit;
}

$data = json_decode($resp, true);

echo "<h3>Decodificado:</h3>";
var_dump($data);
