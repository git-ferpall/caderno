<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../configuracao/env.php';
require_once __DIR__ . '/../configuracao/configuracao_conexao.php'; // conecta no banco local

// Captura o token do AUTH_COOKIE ou fallback para 'token'
$bearer = $_COOKIE[AUTH_COOKIE] ?? ($_COOKIE['token'] ?? '');

// Se não tiver token, mostra já o erro
if (!$bearer) {
    echo "<h3>❌ Nenhum token encontrado (nem AUTH_COOKIE nem token)</h3>";
    exit;
}

// Faz a chamada ao endpoint userinfo
$ch = curl_init('https://caderno.frutag.com.br/sso/userinfo.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $bearer],
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>Resposta bruta:</h2>";
echo "<pre>" . htmlspecialchars($resp) . "</pre>";

$info = json_decode($resp, true);

echo "<h2>Decodificado:</h2>";
echo "<pre>";
var_dump($info);
echo "</pre>";

// Testa se veio ok
if (!is_array($info) || empty($info['ok'])) {
    echo "<h3>⚠️ userinfo retornou vazio ou inválido</h3>";
    $info = ['empresa'=>null, 'razao_social'=>null, 'cpf_cnpj'=>null];
}

// --- Propriedade ativa local ---
$user_id = $info['sub'] ?? null;
$propAtiva = null;

if ($user_id) {
    $stmt = $mysqli->prepare("SELECT endereco_cidade, endereco_uf, nome_razao 
                              FROM propriedades 
                              WHERE user_id = ? AND ativo = 1 
                              LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $propAtiva = $res->fetch_assoc();
    $stmt->close();
}

echo "<h2>Propriedade ativa (local DB):</h2>";
echo "<pre>";
var_dump($propAtiva);
echo "</pre>";
?>
