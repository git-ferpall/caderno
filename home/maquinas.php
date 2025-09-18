<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../configuracao/configuracao_funcoes.php';
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../funcoes/busca_maquinas.php';

if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
verificaSessaoExpirada();

if (!isLogged()) {
    header("Location: ../index.php");
    exit();
}

$cd_usuario_id = $_SESSION['cliente_cod'] ?? null;
$maquinas = [];
if ($cd_usuario_id) {
    $maquinas = buscarMaquinas($cd_usuario_id, $mysqli);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo - Frutag</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body>
    <?php require '../include/loading.php'; ?> 
    <?php include '../include/popups.php'; ?>
    
    <div id="conteudo">
        <?php include '../include/menu.php'; ?>

        <main id="maquinas" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Relação de Máquinas</h2>
            </div>

            <div class="sistema-main">
                <div class="item-box">
                    <!-- Cabeçalho -->
                    <div class="item item-header">
                        <div class="col-nome"><b><span style="font-size: 20px;">Máquina</span></b></div>
                        <div class="col-marca"><b><span style="font-size: 20px;">Marca</span></b></div>
                        <div class="col-tipo"><b><span style="font-size: 20px;">Tipo</span></b></div>
                        <div class="col-propriedade"><b><span style="font-size: 20px;">Propriedade</span></b></div>
                        <div class="item-edit"></div>
                    </div>

                    <!-- Linhas -->
                    <?php
                    if (!empty($maquinas)) {
                        foreach ($maquinas as $maquina) {
                            $id       = htmlspecialchars($maquina['id']);
                            $nome     = htmlspecialchars($maquina['nome']);
                            $marca    = htmlspecialchars($maquina['marca'] ?? '');
                            $tipo     = isset($maquina['tipo']) ? (
                                $maquina['tipo'] == '1' ? 'Motorizado' :
                                ($maquina['tipo'] == '2' ? 'Acoplado' :
                                ($maquina['tipo'] == '3' ? 'Manual' : $maquina['tipo']))
                            ) : '';
                            $nomeProp = htmlspecialchars($maquina['propriedade_nome'] ?? 'Indefinido');

                            $dadosMaquina = [
                                'id' => $id,
                                'nome' => $nome,
                                'marca' => $marca,
                                'tipo' => $maquina['tipo'] ?? '1'
                            ];

                            echo '
                                <div class="item" id="prod-' . $id . '">
                                    <div class="col-nome">' . $nome . '</div>
                                    <div class="col-marca">' . $marca . '</div>
                                    <div class="col-tipo">' . $tipo . '</div>
                                    <div class="col-propriedade">' . $nomeProp . '</div>
                                    <div class="item-edit">
                                        <button class="edit-btn" type="button"
                                            onclick=\'editItem(' . json_encode($dadosMaquina) . ')\'>
                                            <div class="edit-icon icon-pen"></div>
                                        </button>
                                    </div>
                                </div>
                            ';
                        }
                    } else {
                        echo '<div class="item-none">Nenhuma máquina cadastrada.</div>';
                    }
                    ?>
                </div>

                <form action="../funcoes/cadastra_maquina.php" method="POST" class="main-form" id="add-maquina">
                    <input type="hidden" name="m-id" id="m-id">

                    <div class="item-add">
                        <button class="main-btn btn-alter btn-alter-item fundo-verde" 
                            id="maquina-add" 
                            type="button">
                            <div class="btn-icon icon-plus cor-verde"></div>
                            <span class="main-btn-text">Nova máquina</span>
                        </button>
                    </div>

                    <div class="item-add-box" id="item-add-maquina">
                        <div class="item-add-box-p">

                            <div class="form-campo">
                                <label class="item-label" for="m-nome">Nome da máquina</label>
                                <input type="text" class="form-text" name="mnome" id="m-nome" placeholder="Ex: Trator verde, Pulverizador..." required>
                            </div>

                            <div class="form-campo">
                                <label class="item-label" for="m-marca">Marca ou Nome Comercial</label>
                                <input type="text" class="form-text" name="mmarca" id="m-marca" placeholder="Ex: Valmet, John Deere..." required>
                            </div>

                            <div class="form-campo">
                                <label class="item-label" for="m-tipo">Tipo de Máquina</label>
                                <div class="form-radio-box" id="m-tipo">
                                    <label class="form-radio">
                                        <input type="radio" name="mtipo" value="1" checked/>
                                        Motorizado
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="mtipo" value="2" />
                                        Acoplado
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="mtipo" value="3" />
                                        Manual
                                    </label>
                                </div>
                            </div>

                            <div class="form-submit">
                                <button class="item-btn fundo-cinza-b cor-preto" id="form-cancel" type="button" onclick="cancelarEdicao()">
                                    <span class="main-btn-text">Cancelar</span>
                                </button>
                                <button class="item-btn fundo-verde" id="form-save" type="button" onclick="salvarMaquina()">
                                    <span class="main-btn-text">Salvar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>

        <?php include '../include/imports.php'; ?>
    </div>
    
    <script src="../js/maquinas.js"></script>   
    <?php include '../include/footer.php'; ?>
</body>
</html>
