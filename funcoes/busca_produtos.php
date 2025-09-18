<?php
require_once __DIR__ . '/../configuracao/configuracao_funcoes.php';
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
verificaSessaoExpirada();

if (!isLogged()) {
    exit('<div class="item-none">Sessão expirada. Faça login novamente.</div>');
}

$cd_usuario_id = $_SESSION['cliente_cod'] ?? null;

if (!$cd_usuario_id) {
    exit('<div class="item-none">Usuário não logado.</div>');
}

$sql = "SELECT 
            p.produto_id AS id,
            p.produto_nome AS nome,
            p.produto_cultivo AS cultivo,
            p.produto_atributo AS atributo,
            pr.propriedade_nome AS propriedade
        FROM caderno_produtos p
        INNER JOIN caderno_propriedade pr ON pr.propriedade_id = p.propriedade_id
        WHERE p.cd_usuario_id = ?
        ORDER BY pr.propriedade_nome ASC, p.produto_nome ASC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $cd_usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="item-none">Nenhum produto cadastrado.</div>';
    exit;
}

// Cabeçalho
echo '
<div class="item item-header">
    <div class="col-nome" style="text-align: center;"><b>Produto</b></div>
    <div class="col-tipo" style="text-align: center;"><b>Cultivo</b></div>
    <div class="col-marca" style="text-align: center;"><b>Atributo</b></div>
    <div class="col-propriedade" style="text-align: center;"><b>Propriedade</b></div>
    <div class="item-edit" style="text-align: center;"><b>Ações</b></div>
</div>
';

while ($p = $result->fetch_assoc()) {
    $id         = $p['id'];
    $nome       = $p['nome'];
    $cultivo    = $p['cultivo'];
    $atributo   = $p['atributo'];
    $propriedade= $p['propriedade'];

    $dadosProduto = [
        'id'       => $id,
        'nome'     => $nome,
        'cultivo'  => $cultivo,
        'atributo' => $atributo
    ];

    switch ($cultivo) {
        case '1': case 1: $cultivo_legivel = "Convencional"; break;
        case '2': case 2: $cultivo_legivel = "Orgânico"; break;
        case '3': case 3: $cultivo_legivel = "Integrado"; break;
        default:          $cultivo_legivel = $cultivo;
    }

    echo '
        <div class="item" id="prod-' . $id . '">
            <div class="col-nome" style="text-align: center;">' . htmlspecialchars($nome) . '</div>
            <div class="col-tipo" style="text-align: center;">' . htmlspecialchars($cultivo_legivel) . '</div>
            <div class="col-marca" style="text-align: center;">' . htmlspecialchars($atributo) . '</div>
            <div class="col-propriedade" style="text-align: center;">' . htmlspecialchars($propriedade) . '</div>
            <div class="item-edit" style="display: flex; justify-content: center; gap: 10px;">
                <button class="edit-btn" type="button" onclick=\'editItem(' . json_encode($dadosProduto) . ')\' title="Editar">
                    <div class="edit-icon icon-pen"></div>
                </button>
                <button class="edit-btn" type="button" onclick="excluirItem(' . $id . ')" title="Excluir">
                    <div class="edit-icon icon-trash"></div>
                </button>
            </div>

        </div>
    ';
}
$stmt->close();
