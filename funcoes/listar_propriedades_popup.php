<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

// pegar usuário logado
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(["ok" => false, "html" => "<div class='item-none'>Usuário não autenticado.</div>"]);
    exit;
}

// buscar propriedades
$stmt = $mysqli->prepare("SELECT id, nome_razao, ativo FROM propriedades WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$html = '';
if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $ativoClass = $row['ativo'] ? ' ativo' : '';
        $html .= '
            <div class="item item-propriedade fundo-preto v3'.$ativoClass.'" id="prop-'.$row['id'].'">
                <h4 class="item-title">'.htmlspecialchars($row['nome_razao']).'</h4>
                <div class="item-edit">
                    <button class="edit-btn" type="button" onclick="ativarPropriedade('.$row['id'].')">
                        Selecionar
                    </button>
                </div>
            </div>
        ';
    }
} else {
    $html = "<div class='item-none'>Nenhuma propriedade cadastrada.</div>";
}

echo json_encode(["ok" => true, "html" => $html]);
