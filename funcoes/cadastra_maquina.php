<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/busca_propriedade_ativa.php';


session_start();

if (!isset($_SESSION['cliente_cod'])) {
    http_response_code(403);
    echo "Sessão expirada ou não autenticado.";
    exit;
}

$cd_usuario_id = $_SESSION['cliente_cod'] ?? null;
$propriedade_id = $_POST['propriedade_id'] ?? null; // se estiver usando
$maquinario_id  = $_POST['m-id'] ?? null;
$nome           = trim($_POST['mnome'] ?? '');
$marca          = trim($_POST['mmarca'] ?? '');
$tipo           = trim($_POST['mtipo'] ?? '1');

// Validação básica
if (empty($nome) || empty($marca)) {
    http_response_code(400);
    echo "Preencha todos os campos obrigatórios.";
    exit;
}

// 🔥 Se tem maquinario_id → UPDATE
if (!empty($maquinario_id)) {
    $sql = "UPDATE caderno_maquinario 
            SET maquinario_nome = ?, 
                maquinario_marca = ?, 
                maquinario_tipo = ? 
            WHERE maquinario_id = ? AND cd_usuario_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('sssii', $nome, $marca, $tipo, $maquinario_id, $cd_usuario_id);
} else {
    // 🔥 Se não tem → INSERT
    $sql = "INSERT INTO caderno_maquinario 
            (propriedade_id, cd_usuario_id, maquinario_nome, maquinario_marca, maquinario_tipo) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iisss', $propriedade_id, $cd_usuario_id, $nome, $marca, $tipo);
}

if ($stmt->execute()) {
    header("Location: ../home/maquinas.php");
    exit;
} else {
    http_response_code(500);
    echo "Erro ao salvar: " . $stmt->error;
}

$stmt->close();
$mysqli->close();
?>