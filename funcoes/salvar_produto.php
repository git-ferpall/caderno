<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'err'=>'method_not_allowed']);
    exit;
}

try {
    // valida JWT
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

    error_log("SALVAR_PRODUTO: POST=" . json_encode($_POST));
    error_log("SALVAR_PRODUTO: user_id=" . var_export($user_id, true));

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'err'=>'unauthorized']);
        exit;
    }

    // pega dados do formulário
    $nome = trim($_POST['pnome'] ?? '');
    $tipo = $_POST['ptipo'] ?? '';
    $atr  = $_POST['patr'] ?? '';

    error_log("SALVAR_PRODUTO: nome=$nome tipo=$tipo atr=$atr");

    if ($nome === '' || $tipo === '' || $atr === '') {
        echo json_encode(["ok" => false, "error" => "Dados incompletos"]);
        exit;
    }

    // mapear valores
    $mapTipo = ['1'=>'convencional','2'=>'organico','3'=>'integrado'];
    $mapAtr  = ['hidro'=>'hidro','semi-hidro'=>'semi-hidro','solo'=>'solo'];

    $tipoVal = $mapTipo[$tipo] ?? null;
    $atrVal  = $mapAtr[$atr] ?? null;

    if (!$tipoVal || !$atrVal) {
        echo json_encode(["ok" => false, "error" => "Valores inválidos"]);
        exit;
    }

    // salvar no banco
    $stmt = $mysqli->prepare("INSERT INTO produtos (user_id, nome, tipo, atributo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $nome, $tipoVal, $atrVal);

    if ($stmt->execute()) {
        error_log("SALVAR_PRODUTO: insert OK id=" . $stmt->insert_id);

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json') || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(["ok" => true, "id" => $stmt->insert_id]);
        } else {
            // Se acesso direto → redireciona
            header("Location: /home/produtos.php?sucesso=1");
        }
    } else {
        error_log("SALVAR_PRODUTO: erro no insert " . $stmt->error);
        echo json_encode(["ok" => false, "error" => $stmt->error]);
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("SALVAR_PRODUTO: exception " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'err'=>'db','msg'=>$e->getMessage()]);
}
