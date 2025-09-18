<?php
function buscarPropriedadePorUsuario($cod_usuario, $mysqli) {
    $sql = "SELECT 
                p.propriedade_nome, 
                c.cid_nome AS cidade_nome, 
                u.sigla AS uf_sigla
            FROM caderno_propriedade p
            LEFT JOIN cidade c ON p.cidade_cid_cod = c.cid_cod
            LEFT JOIN uf u ON p.uf_codigo = u.codigo
            WHERE p.propriedade_cod_usuario = ?
            ORDER BY p.propriedade_id DESC LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $cod_usuario);
    $stmt->execute();
    $stmt->bind_result($propriedade_nome, $cidade_nome, $uf_sigla);
    if ($stmt->fetch()) {
        return [
            'propriedade_nome' => $propriedade_nome,
            'cidade_nome'      => $cidade_nome,
            'uf_sigla'         => $uf_sigla,
        ];
    } else {
        return null;
    }
}
?>
