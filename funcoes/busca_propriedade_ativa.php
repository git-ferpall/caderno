<?php
function buscarPropriedadeAtiva($cd_usuario_id, $mysqli) {
    // 1. Verifica se o usuário tem propriedade ativa
    $sql = "SELECT propriedade_ativa FROM caderno_usuario WHERE cd_usuario_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $cd_usuario_id);
    $stmt->execute();
    $stmt->bind_result($propriedade_ativa);
    $stmt->fetch();
    $stmt->close();

    // Se existir propriedade ativa e for maior que 0, retorna ela
    if (!empty($propriedade_ativa) && $propriedade_ativa > 0) {
        return $propriedade_ativa;
    }

    // 2. Se não tiver ativa, busca a primeira propriedade cadastrada
    $sql = "SELECT propriedade_id FROM caderno_propriedade WHERE propriedade_cod_usuario = ? ORDER BY propriedade_id ASC LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $cd_usuario_id);
    $stmt->execute();
    $stmt->bind_result($propriedade_id);
    $stmt->fetch();
    $stmt->close();

    return $propriedade_id ?? null;
}
?>
