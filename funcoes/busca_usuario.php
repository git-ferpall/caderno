<?php

function buscarUsuarioPorCodigo($cod, $mysqli) {
    if (empty($cod)) {
        return false;
    }

    $stmt = $mysqli->prepare("SELECT * FROM cliente WHERE cli_cod = ? LIMIT 1");
    $stmt->bind_param("i", $cod);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $usuario = $result->fetch_assoc()) {
        return $usuario;
    }
    return false;
}