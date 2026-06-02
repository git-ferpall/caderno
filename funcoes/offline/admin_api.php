<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$admin_id = offlineRequireAdmin($mysqli);
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

switch ($acao) {

    case 'listar_usuarios':
        $q = trim($_GET['q'] ?? '');
        $sql = "
            SELECT p.user_id,
                   MAX(p.nome_razao) AS nome_razao,
                   MAX(p.email) AS email,
                   MAX(CASE WHEN p.ativo = 1 THEN p.nome_razao END) AS propriedade_ativa,
                   COALESCE(MAX(ou.habilitado), 1) AS offline_habilitado,
                   MAX(ou.atualizado_em) AS atualizado_em
            FROM propriedades p
            LEFT JOIN offline_usuarios ou ON ou.user_id = p.user_id
        ";
        if ($q !== '') {
            $like = '%' . $mysqli->real_escape_string($q) . '%';
            $sql .= " WHERE p.nome_razao LIKE '$like' OR p.email LIKE '$like' OR CAST(p.user_id AS CHAR) LIKE '$like'";
        }
        $sql .= " GROUP BY p.user_id ORDER BY nome_razao ASC LIMIT 200";
        $rows = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);
        offlineJson(['ok' => true, 'usuarios' => $rows]);

    case 'toggle_usuario':
        $target = (int)($_POST['user_id'] ?? 0);
        $habilitar = (int)($_POST['habilitar'] ?? 0) === 1;
        if ($target <= 0) {
            offlineJson(['ok' => false, 'msg' => 'user_id inválido.'], 400);
        }
        if ($habilitar) {
            // Volta ao padrão (todos habilitados): remove exceção de bloqueio
            $stmt = $mysqli->prepare("DELETE FROM offline_usuarios WHERE user_id = ? AND habilitado = 0");
            $stmt->bind_param('i', $target);
        } else {
            $stmt = $mysqli->prepare("
                INSERT INTO offline_usuarios (user_id, habilitado, habilitado_por)
                VALUES (?, 0, ?)
                ON DUPLICATE KEY UPDATE habilitado = 0, habilitado_por = VALUES(habilitado_por), atualizado_em = NOW()
            ");
            $stmt->bind_param('ii', $target, $admin_id);
        }
        $stmt->execute();
        $stmt->close();
        offlineJson(['ok' => true, 'msg' => $habilitar ? 'Offline reativado (padrão).' : 'Offline desabilitado para este cliente.']);

    case 'listar_admins':
        $rows = $mysqli->query("SELECT user_id, nome, email, criado_em FROM offline_admins ORDER BY criado_em ASC")->fetch_all(MYSQLI_ASSOC);
        offlineJson(['ok' => true, 'admins' => $rows]);

    case 'adicionar_admin':
        $target = (int)($_POST['user_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($target <= 0) {
            offlineJson(['ok' => false, 'msg' => 'Informe o ID do usuário Frutag.'], 400);
        }
        $stmt = $mysqli->prepare("
            INSERT INTO offline_admins (user_id, nome, email, adicionado_por)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE nome = VALUES(nome), email = VALUES(email)
        ");
        $stmt->bind_param('issi', $target, $nome, $email, $admin_id);
        $stmt->execute();
        $stmt->close();
        offlineJson(['ok' => true, 'msg' => 'Administrador adicionado.']);

    case 'remover_admin':
        $target = (int)($_POST['user_id'] ?? 0);
        if ($target <= 0) {
            offlineJson(['ok' => false, 'msg' => 'user_id inválido.'], 400);
        }
        if ($target === $admin_id) {
            $res = $mysqli->query("SELECT COUNT(*) AS c FROM offline_admins");
            if ((int)$res->fetch_assoc()['c'] <= 1) {
                offlineJson(['ok' => false, 'msg' => 'Não é possível remover o único administrador.'], 400);
            }
        }
        $stmt = $mysqli->prepare("DELETE FROM offline_admins WHERE user_id = ?");
        $stmt->bind_param('i', $target);
        $stmt->execute();
        $stmt->close();
        offlineJson(['ok' => true, 'msg' => 'Administrador removido.']);

    default:
        offlineJson(['ok' => false, 'msg' => 'Ação inválida.'], 400);
}
