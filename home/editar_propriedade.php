<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

$id = $_GET['id'] ?? null;

// pega user_id via sessão ou JWT
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$id || !$user_id) {
    die("Propriedade não encontrada ou usuário não logado.");
}

$stmt = $mysqli->prepare("SELECT * FROM propriedades WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$prop = $stmt->get_result()->fetch_assoc();

if (!$prop) {
    die("Propriedade não encontrada.");
}

// pega os dados
$nome     = $prop['nome_razao'];
$email    = $prop['email'];
$cnpj     = ($prop['tipo_doc'] === 'cnpj') ? $prop['cpf_cnpj'] : "";
$cpf      = ($prop['tipo_doc'] === 'cpf') ? $prop['cpf_cnpj'] : "";
$ruaEnder = $prop['endereco_rua'];
$numEnder = $prop['endereco_numero'];
$ufEnder  = $prop['endereco_uf'];
$cidEnder = $prop['endereco_cidade'];
$telCom   = $prop['telefone1'];
$telCom2  = $prop['telefone2'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Propriedade</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body>
    <?php include '../include/menu.php'; ?>
    <?php include '../include/loading.php' ?> 
    <?php include '../include/popups.php' ?>

    <main class="sistema">
        <div class="page-title">
            <h2 class="main-title cor-branco">Editar Propriedade</h2>
        </div>

        <div class="sistema-main">
            <form action="/funcoes/update_propriedade.php" method="POST" class="main-form container">
                

                <!-- Campo escondido para UPDATE -->
                <input type="hidden" name="id" value="<?php echo (int)$prop['id']; ?>">

                <div class="form-campo">
                    <label for="pf-razao">Nome ou Razão Social</label>
                    <input class="form-text" type="text" name="pfrazao" id="pf-razao" 
                        placeholder="Seu nome completo" required
                        value="<?php echo htmlspecialchars($nome); ?>">
                </div>

                <div class="form-campo">
                    <label for="pf-cnpj-cpf">Tipo e N° do Documento</label>
                    <div class="form-box" id="pf-cnpj-cpf">
                        <select name="pftipo" id="pf-tipo" class="form-select form-text f1" required>
                            <option value="cnpj" <?php echo ($prop['tipo_doc']==='cnpj') ? 'selected' : ''; ?>>CNPJ</option>
                            <option value="cpf"  <?php echo ($prop['tipo_doc']==='cpf') ? 'selected' : ''; ?>>CPF</option>
                        </select>

                        <input class="form-text only-num f4" type="text" name="pfcnpj" id="pf-cnpj" 
                            placeholder="12.345.789/0001-10" maxlength="18" 
                            value="<?php echo htmlspecialchars($cnpj); ?>">

                        <input class="form-text only-num f4" type="text" name="pfcpf" id="pf-cpf" 
                            placeholder="123.456.789-10" maxlength="14" 
                            value="<?php echo htmlspecialchars($cpf); ?>">
                    </div>
                </div>

                <div class="form-campo">
                    <label for="pf-email-com">E-mail</label>
                    <input class="form-text" type="email" name="pfemail-com" id="pf-email-com"
                        placeholder="Seu e-mail comercial" required
                        value="<?php echo htmlspecialchars($email); ?>">
                </div>

                <div class="form-box">
                    <div class="form-campo f5">
                        <label for="pf-ender-rua">Endereço</label>
                        <input class="form-text" type="text" name="pfender-rua" id="pf-ender-rua" 
                            placeholder="Rua, logradouro, etc" required
                            value="<?php echo htmlspecialchars($ruaEnder); ?>">
                    </div>
                    <div class="form-campo f2">
                        <label for="pf-ender-num">N°</label>
                        <input type="text" class="form-text form-num only-num" 
                            name="pfender-num" id="pf-ender-num" placeholder="S/N" maxlength="6" 
                            value="<?php echo htmlspecialchars($numEnder); ?>">
                    </div>
                </div>

                <div class="form-box">
                    <div class="form-campo f2">
                        <label for="pf-ender-uf">Estado</label>
                        <select name="pfender-uf" id="pf-ender-uf" class="form-select form-text"
                            data-value="<?php echo htmlspecialchars($ufEnder); ?>" required></select>
                    </div>
                    <div class="form-campo f5">
                        <label for="pf-ender-cid">Cidade</label>
                        <select name="pfender-cid" id="pf-ender-cid" class="form-select form-text"
                            data-value="<?php echo htmlspecialchars($cidEnder); ?>" required></select>
                    </div>
                </div>

                <div class="form-campo">
                    <label for="pf-num1-com">Telefone Comercial</label>
                    <input class="form-text form-tel only-num" type="tel" name="pfnum1-com" id="pf-num1-com"
                        placeholder="(DDD) + Número" maxlength="15" 
                        value="<?php echo htmlspecialchars($telCom); ?>">
                </div>

                <div class="form-campo">
                    <label for="pf-num2-com">Telefone Comercial Secundário</label>
                    <input class="form-text form-tel only-num" type="tel" name="pfnum2-com" id="pf-num2-com"
                        placeholder="(DDD) + Número" maxlength="15" 
                        value="<?php echo htmlspecialchars($telCom2); ?>">
                </div>

                <div class="form-submit">
                    <a href="minhas_propriedades.php" class="main-btn fundo-vermelho">
                        <span class="main-btn-text">Cancelar</span>
                    </a>
                    <button class="main-btn fundo-verde" type="submit">
                        <span class="main-btn-text">Salvar Alterações</span>
                    </button>
                </div>
            </form>
        </div>
    </main>
    
    <?php include '../include/footer.php'; ?>
</body>
</html>
