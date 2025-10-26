<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');
    if (empty($_FILES['arquivo']['name'])) throw new Exception('no_file');

    $permitidos = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];
    $tipo = mime_content_type($_FILES['arquivo']['tmp_name']);
    if (!in_array($tipo, $permitidos)) throw new Exception('invalid_type');

    $nome = basename($_FILES['arquivo']['name']);
    $tamanho = $_FILES['arquivo']['size'];
    $origem = $_POST['origem'] ?? 'upload';
    $user_dir = getUserSiloDir($user_id);

    // Verifica espaço
    $uso = getSiloUso($mysqli, $user_id);
    $limite_bytes = $uso['limite_gb'] * 1024 * 1024 * 1024;
    if ($uso['usado_bytes'] + $tamanho > $limite_bytes)
        throw new Exception('limite_excedido');

    $destino = "$user_dir/$nome";
    if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino))
        throw new Exception('upload_fail');

    $stmt = $mysqli->prepare("
        INSERT INTO silo_arquivos (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, origem)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ississ", $user_id, $nome, $tipo, $tamanho, $destino, $origem);
    $stmt->execute();

    echo json_encode(['ok' => true, 'arquivo' => $nome]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
