<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../funcoes/busca_produtos.php';
require_once __DIR__ . '/../configuracao/configuracao_funcoes.php';

sec_session_start();

if (!isset($_SESSION['cliente_cod'])) {
    http_response_code(403);
    exit("Usuário não autenticado.");
}

$cd_usuario_id = $_SESSION['cliente_cod'];

$produtos = buscarProdutos($cd_usuario_id, $mysqli);

if (!empty($produtos)) {
    foreach ($produtos as $produto) {
        $id         = htmlspecialchars($produto['id']);
        $nome       = htmlspecialchars($produto['nome']);
        $tipoCult   = $produto['tipo'] == 1 ? 'Convencional' :
                      ($produto['tipo'] == 2 ? 'Orgânico' :
                      ($produto['tipo'] == 3 ? 'Integrado' : 'Outro'));
        $atributo   = ucfirst($produto['atributo']);
        $nomeProp   = htmlspecialchars($produto['propriedade_nome'] ?? 'Indefinido');

        $dadosProduto = [
            'id' => $id,
            'nome' => $nome,
            'tipo' => $produto['tipo'],
            'atributo' => $produto['atributo']
        ];

        echo '
        <div class="item" id="prod-' . $id . '">
            <div class="col-nome">' . $nome . '</div>
            <div class="col-tipo">' . $tipoCult . '</div>
            <div class="col-atributo">' . $atributo . '</div>
            <div class="col-propriedade">' . $nomeProp . '</div>
            <div class="item-edit">
                <button class="edit-btn" type="button"
                    onclick=\'editItem(' . json_encode($dadosProduto) . ')\' >
                    <div class="edit-icon icon-pen"></div>
                </button>
                <button class="delete-btn" type="button"
                    onclick="excluirProduto(' . $id . ')">
                    <div class="delete-icon icon-trash"></div>
                </button>
            </div>
        </div>';
    }
} else {
    echo '<div class="item-none">Nenhum produto cadastrado.</div>';
}
?>
