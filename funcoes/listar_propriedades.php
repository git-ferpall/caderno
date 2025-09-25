<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

$payload = verify_jwt();
$user_id = $payload['sub'] ?? 0;

$propriedades = [];
$propriedadeAtiva = null;

if ($user_id) {
    $stmt = $mysqli->prepare("SELECT * FROM propriedades WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $propriedades = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // identifica a ativa (ativo = 1)
    foreach ($propriedades as $p) {
        if (!empty($p['ativo'])) {
            $propriedadeAtiva = $p;
            break;
        }
    }
}
?>

<div class="item-box container">
<?php if (!empty($propriedades)): ?>
    <?php foreach ($propriedades as $prop): ?>
        <div class="item item-propriedade v2 <?= !empty($prop['ativo']) ? 'ativo' : '' ?>" id="prop-<?php echo $prop['id']; ?>">
            <h4 class="item-title">
                <?php echo htmlspecialchars($prop['nome_razao'] ?? 'Sem nome'); ?>
                <?php if (!empty($prop['ativo'])): ?>
                    <span class="badge-ativa">Ativa</span>
                <?php endif; ?>
            </h4>
            <div class="item-edit">
                <a href="propriedade.php?editar=<?php echo $prop['id']; ?>" class="edit-btn">Editar</a>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="item-none">Nenhuma propriedade cadastrada.</div>
<?php endif; ?>
</div>
