<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

$payload = verify_jwt();
if ($payload && !empty($payload['sub'])) {
    $_SESSION['user_id'] = $payload['sub'];
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Usuário não logado");
}

$stmt = $mysqli->prepare("SELECT * FROM propriedades WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$propriedades = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minhas Propriedades</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../include/menu.php'; ?>

    <main class="sistema container">
        <div class="page-title">
            <h2 class="main-title cor-branco">Minhas Propriedades</h2>
        </div>

        <div class="sistema-main">
            <a href="propriedade.php" class="main-btn fundo-verde">+ Nova Propriedade</a>

            <div class="item-box container">
                <?php if (!empty($propriedades)): ?>
                    <?php foreach ($propriedades as $prop): ?>
                        <div class="item item-propriedade v2" id="prop-<?php echo (int)$prop['id']; ?>">
                            <h4 class="item-title">
                                <?php echo htmlspecialchars($prop['nome_razao']); ?>
                                <?php if ($prop['ativo']): ?>
                                    <span style="color: green; font-size: 0.9em;">(Ativa)</span>
                                <?php endif; ?>
                            </h4>
                            <div class="item-edit">
                                <a class="edit-btn" href="propriedade.php?editar=<?php echo (int)$prop['id']; ?>">Editar</a> |
                                <a class="delete-btn" href="/funcoes/excluir_propriedade.php?id=<?php echo (int)$prop['id']; ?>" onclick="return confirm('Deseja excluir esta propriedade?')">Excluir</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="item-none">Nenhuma propriedade cadastrada.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>
</body>
</html>
