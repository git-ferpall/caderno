<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/configuracao_funcoes.php';

sec_session_start();
verificaSessaoExpirada();

$cd_usuario_id = $_SESSION['cliente_cod'] ?? null;
if (!$cd_usuario_id) exit('<div class="item-none">Usuário não logado.</div>');

$sql = "SELECT a.area_id AS id, a.area_nome AS nome, a.area_tipo AS tipo, p.propriedade_nome AS propriedade 
        FROM caderno_areas a 
        INNER JOIN caderno_propriedade p ON a.propriedade_id = p.propriedade_id 
        WHERE a.cd_usuario_id = ? 
        ORDER BY p.propriedade_nome ASC, a.area_nome ASC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $cd_usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="item-none">Nenhuma área cadastrada.</div>';
    exit;
}

echo '
<div class="item item-header">
    <div class="col-nome"><b>Área</b></div>
    <div class="col-tipo"><b>Tipo</b></div>
    <div class="col-propriedade"><b>Propriedade</b></div>
    <div class="item-edit"><b>Ações</b></div>
</div>';

while ($a = $result->fetch_assoc()) {
    switch ((int)$a['tipo']) {
        case 1: $tipo_legivel = "Estufa"; break;
        case 2: $tipo_legivel = "Solo"; break;
        case 3: $tipo_legivel = "Outro"; break;
        default: $tipo_legivel = "Tipo inválido";
    }

    $json = json_encode([
        "id"   => $a['id'],
        "nome" => $a['nome'],
        "tipo" => $a['tipo']
    ]);

    echo '
    <div class="item" id="area-' . $a['id'] . '">
        <div class="col-nome">' . htmlspecialchars($a['nome']) . '</div>
        <div class="col-tipo">' . htmlspecialchars($tipo_legivel) . '</div>
        <div class="col-propriedade">' . htmlspecialchars($a['propriedade']) . '</div>
        <div class="item-edit">
            <button class="edit-btn" type="button" onclick=\'editArea(' . $json . ')\' title="Editar">
                <div class="edit-icon icon-pen"></div>
            </button>
            <button class="edit-btn" type="button" onclick="excluirArea(' . $a['id'] . ')" title="Excluir">
                <div class="edit-icon icon-trash"></div>
            </button>
        </div>
    </div>';
}
