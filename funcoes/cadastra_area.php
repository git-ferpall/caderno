<?php
require_once __DIR__ . '/../configuracao/configuracao_funcoes.php';
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/busca_propriedade_ativa.php';

if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
verificaSessaoExpirada();

if (!isLogged()) {
    exit('Sessão expirada.');
}

$cd_usuario_id = $_SESSION['cliente_cod'] ?? null;

if (!$cd_usuario_id) {
    exit('Usuário não identificado.');
}

$area_nome = trim(filter_input(INPUT_POST, 'anome', FILTER_SANITIZE_SPECIAL_CHARS));
$area_tipo = filter_input(INPUT_POST, 'atipo', FILTER_VALIDATE_INT);

if (!$area_nome || !$area_tipo) {
    exit('Dados inválidos.');
}

// Busca a propriedade ativa do usuário (igual à lógica dos produtos)
$propriedade_id = buscarPropriedadeAtiva($cd_usuario_id, $mysqli);
if (!$propriedade_id) {
    exit('Nenhuma propriedade ativa encontrada.');
}

$stmt = $mysqli->prepare("INSERT INTO caderno_areas (cd_usuario_id, propriedade_id, area_nome, area_tipo) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    exit('Erro ao preparar query.');
}

$stmt->bind_param('iisi', $cd_usuario_id, $propriedade_id, $area_nome, $area_tipo);

if ($stmt->execute()) {
    echo "sucesso";
} else {
    echo "Erro ao salvar a área.";
}

$stmt->close();
