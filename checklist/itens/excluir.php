<?php
require_once '../../config/db.php';

$id = $_GET['id'];

$item = $pdo->query(
    "SELECT modelo_id FROM checklist_modelo_itens WHERE id = $id"
)->fetch(PDO::FETCH_ASSOC);

$pdo->exec("DELETE FROM checklist_modelo_itens WHERE id = $id");

header("Location: ../modelos/editar.php?id={$item['modelo_id']}");
