<?php
require_once __DIR__ . '/busca_propriedade_ativa.php';

function buscarMaquinas($cd_usuario_id, $mysqli) {
    $sql = "SELECT 
                m.maquinario_id AS id, 
                m.maquinario_nome AS nome,
                m.maquinario_marca AS marca,
                m.maquinario_tipo AS tipo,
                p.propriedade_nome AS propriedade_nome
            FROM caderno_maquinario m
            INNER JOIN caderno_propriedade p ON m.propriedade_id = p.propriedade_id
            WHERE m.cd_usuario_id = ?
            ORDER BY p.propriedade_nome ASC, m.maquinario_nome ASC";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $cd_usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $maquinas = [];
    while ($row = $result->fetch_assoc()) {
        $maquinas[] = $row;
    }
    $stmt->close();
    return $maquinas;
}
?>
