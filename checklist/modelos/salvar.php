<?php
/**
 * Salva (cria ou edita) um modelo de checklist
 * Stack: MySQLi + Sessão + JWT (SSO)
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

session_start();

/* 🔐 Recupera user_id (sessão → JWT) */
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    http_response_code(401);
    die('Usuário não autenticado');
}

/* 📥 Dados do formulário */
$id        = $_POST['id'] ?? null;
$titulo    = trim($_POST['titulo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$publico   = isset($_POST['publico']) ? 1 : 0;

/* 🧠 Regra de negócio:
   - modelo público → criado_por = NULL
   - modelo privado → criado_por = user_id
*/
$criado_por = $publico ? null : $user_id;

/* 🚫 Validação mínima */
if ($titulo === '') {
    die('Título é obrigatório');
}

/* 💾 UPDATE */
if ($id) {

    $sql = "
        UPDATE checklist_modelos
        SET
            titulo = ?,
            descricao = ?,
            publico = ?,
            criado_por = ?
        WHERE id = ?
    ";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
        "ssiii",
        $titulo,
        $descricao,
        $publico,
        $criado_por,
        $id
    );
    $stmt->execute();
    $stmt->close();

/* 💾 INSERT */
} else {

    $sql = "
        INSERT INTO checklist_modelos
            (titulo, descricao, publico, criado_por)
        VALUES (?, ?, ?, ?)
    ";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
        "ssii",
        $titulo,
        $descricao,
        $publico,
        $criado_por
    );
    $stmt->execute();
    $stmt->close();
}

/* 🔁 Volta para lista de modelos */
header('Location: index.php');
exit;
