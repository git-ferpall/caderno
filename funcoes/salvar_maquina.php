<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Identifica user_id via sessão ou JWT
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }

    if (!$user_id) {
        echo json_encode(["ok" => false, "error" => "Usuário não autenticado"]);
        exit;
    }

    // Propriedade ativa do usuário
    $prop = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $prop->bind_param("i", $user_id);
    $prop->execute();
    $res = $prop->get_result()->fetch_assoc();
    $prop_id = $res['id'] ?? null;
    $prop->close();

    if (!$prop_id) {
        echo json_encode(["ok" => false, "error" => "Nenhuma propriedade ativa encontrada"]);
        exit;
    }

    // Dados do formulário
    $id    = intval($_POST['id'] ?? 0);
    $nome  = trim($_POST['mnome'] ?? '');
    $marca = trim($_POST['mmarca'] ?? '');
    $tipo  = $_POST['mtipo'] ?? '';

    $mapTipo = ['1'=>'motorizado','2'=>'acoplado','3'=>'manual'];
    $tipoVal = $mapTipo[$tipo] ?? null;

    if ($nome === '' || $marca === '' || !$tipoVal) {
        echo json_encode(["ok" => false, "error" => "Preencha todos os campos"]);
        exit;
    }

    if ($id > 0) {
        // Atualiza máquina existente
        $stmt = $mysqli->prepare("
            UPDATE maquinas 
            SET nome = ?, marca = ?, tipo = ? 
            WHERE id = ? AND user_id = ? AND propriedade_id = ?
        ");
        $stmt->bind_param("sssiii", $nome, $marca, $tipoVal, $id, $user_id, $prop_id);
    } else {
        // Insere nova máquina
        $stmt = $mysqli->prepare("
            INSERT INTO maquinas (user_id, propriedade_id, nome, marca, tipo) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisss", $user_id, $prop_id, $nome, $marca, $tipoVal);
    }

    if ($stmt->execute()) {
        echo json_encode(["ok" => true, "id" => $id ?: $stmt->insert_id]);
    } else {
        echo json_encode(["ok" => false, "error" => $stmt->error]);
    }

    $stmt->close();

} catch (Throwable $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
