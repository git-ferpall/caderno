<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

$payload = verify_jwt();
$user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

$produtos = [];
if ($user_id) {
    $stmt = $mysqli->prepare("SELECT * FROM produtos WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $produtos = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="item-box container">
    <?php if (!empty($produtos)): ?>
        <?php foreach ($produtos as $produto): ?>
            <div class="item" id="prod-<?php echo $produto['id']; ?>">
                <h4 class="item-title"><?php echo htmlspecialchars($produto['nome']); ?></h4>
                <div class="item-edit">
                    <button class="edit-btn" type="button" onclick='editProduto(<?php echo json_encode($produto); ?>)'>
                        <div class="edit-icon icon-pen"></div>
                    </button>
                    <button class="edit-btn fundo-vermelho" type="button" onclick="removerProduto(<?php echo $produto['id']; ?>)">
                        <div class="edit-icon icon-x"></div>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="item-none">Nenhum produto cadastrado.</div>
    <?php endif; ?>
</div>
