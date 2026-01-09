<?php
require_once '../../config/db.php';

$modelo_id = $_POST['modelo_id'];
$descricao = $_POST['descricao'];

$sql = "
INSERT INTO checklist_modelo_itens (modelo_id, descricao)
VALUES (?, ?)
";
$pdo->prepare($sql)->execute([$modelo_id, $descricao]);

header("Location: ../modelos/editar.php?id=$modelo_id");
