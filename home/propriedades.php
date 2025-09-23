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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Propriedades - Caderno de Campo</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body>
    <?php include '../include/loading.php'; ?> 
    <?php include '../include/popups.php'; ?>

    <div id="conteudo">
        <?php include '../include/menu.php'; ?>  

        <main id="propriedades" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Minhas Propriedades</h2>
            </div>

            <div class="sistema-main container">
                <div class="form-submit" style="margin-bottom:20px;">
                    <a href="propriedade.php" class="main-btn fundo-verde">
                        <span class="main-btn-text">+ Nova Propriedade</span>
                    </a>
                </div>

                <div class="item-box container">
                    <?php if (!empty($propriedades)): ?>
                        <?php foreach ($propriedades as $prop): ?>
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
            </div>
        </main>

        <?php include '../include/imports.php'; ?>
    </div>
    
    <?php include '../include/footer.php'; ?>
</body>
</html>
