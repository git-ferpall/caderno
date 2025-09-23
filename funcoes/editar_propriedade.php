<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

session_start();

// garante user_id via token ou sessão
$payload = verify_jwt();
if ($payload && !empty($payload['sub'])) {
    $_SESSION['user_id'] = $payload['sub'];
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Usuário não logado");
}

// pega id da propriedade
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("ID inválido");
}

// carrega dados da propriedade
$stmt = $mysqli->prepare("SELECT * FROM propriedades WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$prop = $stmt->get_result()->fetch_assoc();

if (!$prop) {
    die("Propriedade não encontrada ou não pertence a este usuário.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Propriedade</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../include/menu.php'; ?>
<?php include '../include/loading.php'; ?> 
<?php include '../include/popups.php'; ?>

<main class="sistema">
    <div class="page-title">
        <h2 class="main-title cor-branco">Editar Propriedade</h2>
    </div>

    <form action="/funcoes/salvar_propriedade.php" method="POST" class="main-form container">
        <!-- campo oculto para saber que é edição -->
        <input type="hidden" name="id" value="<?php echo (int)$prop['id']; ?>">

        <div class="form-campo">
            <label>Nome ou Razão Social</label>
            <input class="form-text" type="text" name="pfrazao" value="<?php echo htmlspecialchars($prop['nome_razao']); ?>" required>
        </div>

        <div class="form-campo">
            <label>Tipo e N° do Documento</label>
            <select name="pftipo" required>
                <option value="cnpj" <?php if($prop['tipo_doc']==='cnpj') echo 'selected'; ?>>CNPJ</option>
                <option value="cpf"  <?php if($prop['tipo_doc']==='cpf')  echo 'selected'; ?>>CPF</option>
            </select>
            <input class="form-text" type="text" name="pfcnpj" value="<?php echo htmlspecialchars($prop['cpf_cnpj']); ?>">
        </div>

        <div class="form-campo">
            <label>Email</label>
            <input type="email" name="pfemail-com" value="<?php echo htmlspecialchars($prop['email']); ?>">
        </div>

        <div class="form-campo">
            <label>Endereço</label>
            <input type="text" name="pfender-rua" value="<?php echo htmlspecialchars($prop['endereco_rua']); ?>">
        </div>

        <div class="form-campo">
            <label>Número</label>
            <input type="text" name="pfender-num" value="<?php echo htmlspecialchars($prop['endereco_numero']); ?>">
        </div>

        <div class="form-campo">
            <label>Estado</label>
            <input type="text" name="pfender-uf" maxlength="2" value="<?php echo htmlspecialchars($prop['endereco_uf']); ?>">
        </div>

        <div class="form-campo">
            <label>Cidade</label>
            <input type="text" name="pfender-cid" value="<?php echo htmlspecialchars($prop['endereco_cidade']); ?>">
        </div>

        <div class="form-campo">
            <label>Telefone 1</label>
            <input type="text" name="pfnum1-com" value="<?php echo htmlspecialchars($prop['telefone1']); ?>">
        </div>

        <div class="form-campo">
            <label>Telefone 2</label>
            <input type="text" name="pfnum2-com" value="<?php echo htmlspecialchars($prop['telefone2']); ?>">
        </div>

        <div class="form-submit">
            <a href="minhas_propriedades.php" class="main-btn fundo-vermelho">Cancelar</a>
            <button class="main-btn fundo-verde" type="submit">Salvar</button>
        </div>
    </form>
</main>
<?php include '../include/imports.php'; ?>
<?php include '../include/footer.php'; ?>
</body>
</html>
