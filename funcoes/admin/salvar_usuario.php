<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

[$uid, $perfil] = adminRequirePerfil($mysqli, ['admin', 'representante']);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    adminJson(['ok' => false, 'msg' => 'Método inválido.'], 405);
}

$acao = $_POST['acao'] ?? '';

/* ============================================================
 * CRIAR usuário local
 * ============================================================ */
if ($acao === 'criar') {
    $novoPerfil = $_POST['perfil'] ?? 'usuario';
    if ($perfil !== 'admin') {
        $novoPerfil = 'usuario'; // representante só cria clientes comuns
    }

    try {
        $novoId = usuarioCriarLocal($mysqli, [
            'nome'   => $_POST['nome'] ?? '',
            'login'  => $_POST['login'] ?? '',
            'email'  => $_POST['email'] ?? '',
            'senha'  => $_POST['senha'] ?? '',
            'perfil' => $novoPerfil,
        ], $uid);
    } catch (InvalidArgumentException $e) {
        adminJson(['ok' => false, 'msg' => $e->getMessage()], 400);
    } catch (Throwable $e) {
        adminJson(['ok' => false, 'msg' => caderno_erro_msg($e)], 500);
    }

    adminJson(['ok' => true, 'msg' => 'Usuário criado com sucesso.', 'id' => $novoId]);
}

/* ============================================================
 * ATUALIZAR usuário (nome, e-mail, perfil, ativo)
 * ============================================================ */
if ($acao === 'atualizar') {
    $targetId = (int)($_POST['user_id'] ?? 0);
    if ($targetId <= 0) {
        adminJson(['ok' => false, 'msg' => 'user_id inválido.'], 400);
    }

    $target = usuarioBuscarPorId($mysqli, $targetId);
    if (!$target && $perfil === 'admin') {
        // usuário Frutag legado ainda não provisionado
        $target = usuarioGarantirFrutag($mysqli, $targetId);
    }
    if (!$target) {
        adminJson(['ok' => false, 'msg' => 'Usuário não encontrado.'], 404);
    }
    if ($perfil !== 'admin' && (int)$target['criado_por'] !== $uid) {
        adminJson(['ok' => false, 'msg' => 'Você só pode alterar clientes cadastrados por você.'], 403);
    }

    // Liberação do Frutibank (tabela própria, só admin)
    $frutibankAlterado = false;
    if (isset($_POST['frutibank'])) {
        if ($perfil !== 'admin') {
            adminJson(['ok' => false, 'msg' => 'Apenas administradores liberam o Frutibank.'], 403);
        }
        require_once __DIR__ . '/../frutibank/helpers.php';
        frutibankEnsureSchema($mysqli);
        if ((int)$_POST['frutibank'] === 1) {
            $stmt = $mysqli->prepare('INSERT IGNORE INTO frutibank_usuarios (user_id, habilitado_por) VALUES (?, ?)');
            $stmt->bind_param('ii', $targetId, $uid);
        } else {
            $stmt = $mysqli->prepare('DELETE FROM frutibank_usuarios WHERE user_id = ?');
            $stmt->bind_param('i', $targetId);
        }
        $stmt->execute();
        $stmt->close();
        $frutibankAlterado = true;
    }

    $sets = [];
    $tipos = '';
    $vals = [];

    if (isset($_POST['nome'])) {
        $nome = trim((string)$_POST['nome']);
        if ($nome === '') adminJson(['ok' => false, 'msg' => 'Nome não pode ficar vazio.'], 400);
        $sets[] = 'nome = ?';
        $tipos .= 's';
        $vals[] = $nome;
    }

    if (isset($_POST['email'])) {
        $email = strtolower(trim((string)$_POST['email']));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            adminJson(['ok' => false, 'msg' => 'E-mail inválido.'], 400);
        }
        $sets[] = 'email = ?';
        $tipos .= 's';
        $vals[] = $email !== '' ? $email : null;
    }

    if (isset($_POST['perfil'])) {
        if ($perfil !== 'admin') {
            adminJson(['ok' => false, 'msg' => 'Apenas administradores alteram perfis.'], 403);
        }
        $novoPerfil = (string)$_POST['perfil'];
        if (!in_array($novoPerfil, USUARIO_PERFIS, true)) {
            adminJson(['ok' => false, 'msg' => 'Perfil inválido.'], 400);
        }
        $sets[] = 'perfil = ?';
        $tipos .= 's';
        $vals[] = $novoPerfil;
    }

    if (isset($_POST['ativo'])) {
        $ativo = (int)$_POST['ativo'] === 1 ? 1 : 0;
        $sets[] = 'ativo = ?';
        $tipos .= 'i';
        $vals[] = $ativo;
    }

    if (!$sets) {
        if ($frutibankAlterado) {
            adminJson(['ok' => true, 'msg' => 'Usuário atualizado.']);
        }
        adminJson(['ok' => false, 'msg' => 'Nada para atualizar.'], 400);
    }

    // Não deixar o sistema ficar sem nenhum admin ativo
    $viraNaoAdmin = (isset($_POST['perfil']) && $_POST['perfil'] !== 'admin')
        || (isset($_POST['ativo']) && (int)$_POST['ativo'] !== 1);
    if ($target['perfil'] === 'admin' && (int)$target['ativo'] === 1 && $viraNaoAdmin) {
        $res = $mysqli->query("SELECT COUNT(*) AS c FROM usuarios_caderno WHERE perfil = 'admin' AND ativo = 1");
        if ((int)$res->fetch_assoc()['c'] <= 1) {
            adminJson(['ok' => false, 'msg' => 'Não é possível rebaixar ou desativar o único administrador ativo.'], 400);
        }
    }

    $tipos .= 'i';
    $vals[] = $targetId;
    $stmt = $mysqli->prepare('UPDATE usuarios_caderno SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $stmt->bind_param($tipos, ...$vals);
    $stmt->execute();
    $stmt->close();

    adminJson(['ok' => true, 'msg' => 'Usuário atualizado.']);
}

adminJson(['ok' => false, 'msg' => 'Ação inválida.'], 400);
