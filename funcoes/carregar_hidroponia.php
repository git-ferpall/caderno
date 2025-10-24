<?php
require_once "../configuracao/configuracao_conexao.php";

function carregarHidroponia($user_id) {
    global $mysqli;
    
    $areas = [];
    $sql = "SELECT * FROM areas WHERE user_id = ? AND tipo = 'estufa'";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $resAreas = $stmt->get_result();

    while ($area = $resAreas->fetch_assoc()) {
        // Buscar estufas da área
        $stmt2 = $mysqli->prepare("SELECT * FROM estufas WHERE area_id = ?");
        $stmt2->bind_param("i", $area['id']);
        $stmt2->execute();
        $resEstufas = $stmt2->get_result();
        $area['estufas'] = [];

        while ($estufa = $resEstufas->fetch_assoc()) {
            // Buscar bancadas da estufa
            $stmt3 = $mysqli->prepare("SELECT * FROM bancadas WHERE estufa_id = ?");
            $stmt3->bind_param("i", $estufa['id']);
            $stmt3->execute();
            $resBancadas = $stmt3->get_result();
            $estufa['bancadas'] = $resBancadas->fetch_all(MYSQLI_ASSOC);

            $area['estufas'][] = $estufa;
        }

        $areas[] = $area;
    }

    return $areas;
}
?>
