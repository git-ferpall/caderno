<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../configuracao/configuracao_funcoes.php';
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../funcoes/busca_propriedade_ativa.php';


if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
verificaSessaoExpirada();

if (!isLogged()) {
    exit("Erro: sessão expirada. Faça login novamente.");
}

$usuario_id = $_SESSION['cliente_cod'] ?? null;
if (!$usuario_id) {
    exit("Erro: usuário não identificado.");
}

// Recebendo os dados do formulário
$produto_id    = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
$nome          = trim(filter_input(INPUT_POST, 'pnome', FILTER_SANITIZE_STRING));
$cultivo       = filter_input(INPUT_POST, 'ptipo', FILTER_VALIDATE_INT);
$atributo      = filter_input(INPUT_POST, 'patr', FILTER_SANITIZE_STRING);

// Busca propriedade ativa do usuário
$propriedade_id = buscarPropriedadeAtiva($usuario_id, $mysqli);
if (!$propriedade_id) {
    exit("Erro: nenhuma propriedade ativa encontrada.");
}

// Verifica campos obrigatórios
if (!$nome || !$cultivo || !$atributo) {
    exit("Erro: preencha todos os campos obrigatórios.");
}

// INSERT ou UPDATE
if (!$produto_id) {
    // INSERIR NOVO PRODUTO
    $sql = "INSERT INTO caderno_produtos (produto_nome, produto_cultivo, produto_atributo, cd_usuario_id, propriedade_id)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        exit("Erro ao preparar inserção: " . $mysqli->error);
    }

    $stmt->bind_param("ssiii", $nome, $cultivo, $atributo, $usuario_id, $propriedade_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Produto inserido com sucesso.";
    } else {
        echo "Erro ao inserir produto.";
    }

    $stmt->close();

} else {
    // ATUALIZAR PRODUTO EXISTENTE
    $sql = "UPDATE caderno_produtos 
            SET produto_nome = ?, produto_cultivo = ?, produto_atributo = ?
            WHERE produto_id = ? AND cd_usuario_id = ?";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        exit("Erro ao preparar atualização: " . $mysqli->error);
    }

    $stmt->bind_param("sssii", $nome, $cultivo, $atributo, $produto_id, $usuario_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Produto atualizado com sucesso.";
    } else {
        echo "Nenhuma alteração feita ou erro ao atualizar.";
    }

    $stmt->close();
}
?>
