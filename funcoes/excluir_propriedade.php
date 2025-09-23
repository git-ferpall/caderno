<?php
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../funcoes/carregar_propriedade.php';

$user_id = $_SESSION['user_id'] ?? null;
$propriedades = [];

if ($user_id) {
    $propriedades = carregarPropriedades($mysqli, $user_id);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Minhas Propriedades</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <h2>Minhas Propriedades</h2>

  <a href="propriedade.php">+ Nova Propriedade</a>

  <div class="item-box container">
    <?php if (!empty($propriedades)): ?>
        <?php foreach ($propriedades as $prop): ?>
            <pre><?php print_r($prop); ?></pre> <!-- DEBUG -->
            <div class="item item-propriedade v2" id="prop-<?php echo (int)$prop['id']; ?>">
                <h4 class="item-title">
                    <?php echo !empty($prop['nome_razao']) 
                        ? htmlspecialchars($prop['nome_razao']) 
                        : 'Sem nome'; ?>
                </h4>
                <div class="item-edit">
                    <a class="edit-btn" href="propriedade.php?editar=<?php echo (int)$prop['id']; ?>">
                        Editar
                    </a>
                    |
                    <a class="delete-btn" 
                       href="/funcoes/excluir_propriedade.php?id=<?php echo (int)$prop['id']; ?>"
                       onclick="return confirm('Tem certeza que deseja excluir esta propriedade?')">
                        Excluir
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="item-none">Nenhuma propriedade cadastrada.</div>
    <?php endif; ?>
</div>

</body>
</html>
