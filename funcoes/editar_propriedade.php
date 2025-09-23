<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';

$id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$id || !$user_id) {
    die("Propriedade não encontrada ou usuário não logado.");
}

// Busca os dados da propriedade
$stmt = $mysqli->prepare("SELECT * FROM propriedades WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$prop = $stmt->get_result()->fetch_assoc();

if (!$prop) {
    die("Propriedade não encontrada.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Propriedade</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../include/menu.php'; ?>

    <main class="sistema container">
        <div class="page-title">
            <h2 class="main-title">Editar Propriedade</h2>
        </div>

        <form action="/funcoes/salvar_propriedade.php" method="POST" class="main-form">
            <input type="hidden" name="id" value="<?php echo (int)$prop['id']; ?>">

            <div class="form-campo">
                <label for="pf-razao">Nome ou Razão Social</label>
                <input type="text" name="pfrazao" id="pf-razao" class="form-text"
                       value="<?php echo htmlspecialchars($prop['nome_razao']); ?>" required>
            </div>

            <div class="form-campo">
                <label for="pftipo">Tipo de Documento</label>
                <select name="pftipo" id="pftipo" class="form-select form-text">
                    <option value="cnpj" <?php echo $prop['tipo_doc'] === 'cnpj' ? 'selected' : ''; ?>>CNPJ</option>
                    <option value="cpf" <?php echo $prop['tipo_doc'] === 'cpf' ? 'selected' : ''; ?>>CPF</option>
                </select>
            </div>

            <div class="form-campo">
                <label for="pfcnpj">CPF / CNPJ</label>
                <input type="text" name="pfcnpj" id="pfcnpj" class="form-text"
                       value="<?php echo htmlspecialchars($prop['cpf_cnpj']); ?>">
            </div>

            <div class="form-campo">
                <label for="pfemail">E-mail</label>
                <input type="email" name="pfemail-com" id="pfemail" class="form-text"
                       value="<?php echo htmlspecialchars($prop['email']); ?>">
            </div>

            <div class="form-campo">
                <label for="pf-ender-rua">Endereço</label>
                <input type="text" name="pfender-rua" id="pf-ender-rua" class="form-text"
                       value="<?php echo htmlspecialchars($prop['endereco_rua']); ?>">
            </div>

            <div class="form-campo">
                <label for="pf-ender-num">Número</label>
                <input type="text" name="pfender-num" id="pf-ender-num" class="form-text"
                       value="<?php echo htmlspecialchars($prop['endereco_numero']); ?>">
            </div>

            <div class="form-campo">
                <label for="pf-ender-uf">Estado</label>
                <input type="text" name="pfender-uf" id="pf-ender-uf" class="form-text"
                       value="<?php echo htmlspecialchars($prop['endereco_uf']); ?>">
            </div>

            <div class="form-campo">
                <label for="pf-ender-cid">Cidade</label>
                <input type="text" name="pfender-cid" id="pf-ender-cid" class="form-text"
                       value="<?php echo htmlspecialchars($prop['endereco_cidade']); ?>">
            </div>

            <div class="form-campo">
                <label for="pfnum1-com">Telefone 1</label>
                <input type="text" name="pfnum1-com" id="pfnum1-com" class="form-text"
                       value="<?php echo htmlspecialchars($prop['telefone1']); ?>">
            </div>

            <div class="form-campo">
                <label for="pfnum2-com">Telefone 2</label>
                <input type="text" name="pfnum2-com" id="pfnum2-com" class="form-text"
                       value="<?php echo htmlspecialchars($prop['telefone2']); ?>">
            </div>

            <div class="form-submit">
                <button type="submit" class="main-btn fundo-verde">Salvar</button>
                <a href="minhas_propriedades.php" class="main-btn fundo-vermelho">Cancelar</a>
            </div>
        </form>
    </main>

    <?php include '../include/footer.php'; ?>
</body>
</html>
