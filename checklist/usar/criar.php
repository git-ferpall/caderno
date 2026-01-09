<?php
require_once '../../config/db.php';
session_start();

$user_id   = $_SESSION['user_id'];
$modelo_id = $_POST['modelo_id'];

$pdo->beginTransaction();

try {

    // 1️⃣ Buscar modelo
    $modelo = $pdo->prepare(
        "SELECT titulo FROM checklist_modelos WHERE id = ?"
    );
    $modelo->execute([$modelo_id]);
    $modelo = $modelo->fetch(PDO::FETCH_ASSOC);

    if (!$modelo) {
        throw new Exception('Modelo não encontrado');
    }

    // 2️⃣ Criar checklist (instância)
    $sql = "
        INSERT INTO checklists (modelo_id, user_id, titulo)
        VALUES (?, ?, ?)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $modelo_id,
        $user_id,
        $modelo['titulo']
    ]);

    $checklist_id = $pdo->lastInsertId();

    // 3️⃣ Copiar itens do modelo → checklist_itens
    $sql = "
        INSERT INTO checklist_itens (checklist_id, descricao)
        SELECT ?, descricao
        FROM checklist_modelo_itens
        WHERE modelo_id = ?
        ORDER BY ordem ASC, id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$checklist_id, $modelo_id]);

    $pdo->commit();

    // 4️⃣ Redirecionar para preenchimento
    header("Location: ../preencher/index.php?id=$checklist_id");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Erro ao criar checklist: " . $e->getMessage();
}
