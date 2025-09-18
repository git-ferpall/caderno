<?php
require_once __DIR__ . '/../configuracao/configuracao_funcoes.php';
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

sec_session_start();
verificaSessaoExpirada();

$cd_usuario_id = $_SESSION['cliente_cod'] ?? null;
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$cd_usuario_id || !$id) {
    exit('ID inválido.');
}

$stmt = $mysqli->prepare("DELETE FROM caderno_areas WHERE cd_usuario_id = ? AND area_id = ?");
$stmt->bind_param("ii", $cd_usuario_id, $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "sucesso";
} else {
    echo "Erro ao excluir.";
}

$stmt->close();
