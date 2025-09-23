<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

function carregarPropriedades($mysqli, $user_id) {
    $stmt = $mysqli->prepare("SELECT * FROM propriedades WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}

function carregarPropriedadePorId($mysqli, $user_id, $id) {
    $stmt = $mysqli->prepare("SELECT * FROM propriedades WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
