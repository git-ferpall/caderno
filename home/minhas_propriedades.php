<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

$payload = verify_jwt();
$user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

if (!$user_id) {
    die("Usuário não logado");
}

$stmt = $mysqli->prepare("SELECT * FROM propriedades WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$propriedades = [];
while ($row = $res->fetch_assoc()) {
    $propriedades[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minhas Propriedades</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .item-box { margin: 20px 0; }
        .item { background: #f5f5f5; padding: 15px; margin-bottom: 10px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .item-title { margin: 0; font-size: 16px; font-weight: bold; }
        .item-edit a { margin-left: 10px; text-decoration: none; font-size: 14px; padding: 5px 12px; border-radius: 6px; }
        .edit-btn { background: #007bff; color: #fff; }
        .delete-btn { background: #dc3545; color: #fff; }
        .edit-btn:hover { background: #0056b3; }
        .delete-btn:hover { background: #a71d2a; }
        .novo-btn { display: block; text-align: center; background: #28a745; color: #fff; padding: 10px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; text-decoration: none; }
        .novo-btn:hover { background: #1e7e34; }
    </style>
</head>
<body>
    <?php include '../include/menu.php'; ?>

    <main class="sistema">
        <div class="page-title">
            <h2 class="main-title">Minhas Propriedades</h2>
        </div>

        <div class="sistema-main container">
            <a href="editar_propriedade.php" class="novo-btn">+ Nova Propriedade</a>

            <div class="item-box">
                <?php if (!empty($propriedades)): ?>
                    <?php foreach ($propriedades as $prop): ?>
                        <div class="item" id="prop-<?php echo (int)($prop['id'] ?? 0); ?>">
                            <h4 class="item-title">
                                <?php echo htmlspecialchars($prop['nome_razao'] ?? 'Sem nome'); ?>
                            </h4>
                            <div class="item-edit">
                                <a class="edit-btn" href="editar_propriedade.php?id=<?php echo (int)($prop['id'] ?? 0); ?>">Editar</a>
                                <a class="delete-btn" href="/funcoes/excluir_propriedade.php?id=<?php echo (int)($prop['id'] ?? 0); ?>" onclick="return confirm('Tem certeza que deseja excluir esta propriedade?');">Excluir</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nenhuma propriedade cadastrada.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>
</body>
</html>
