<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

// Recupera o user_id pela sessão ou JWT
$user_id = caderno_require_user_id();
$maquinas = [];
if ($user_id) {
    // Só pega da propriedade ativa
    $sql = "SELECT m.id, m.nome, m.marca, m.tipo, m.created_at
              FROM maquinas m
              JOIN propriedades p ON m.propriedade_id = p.id
             WHERE p.user_id = ? AND p.ativo = 1
             ORDER BY m.created_at DESC";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $maquinas = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
