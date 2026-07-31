<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

[$contaId, $papel, $funcId] = contaRequireGestao();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    contaJson(['ok' => false, 'msg' => 'Método inválido.'], 405);
}

$acao = $_POST['acao'] ?? '';

/* ============================================================
 * CRIAR funcionário da conta
 * ============================================================ */
if ($acao === 'criar') {
    $papelNovo = (string)($_POST['papel_conta'] ?? 'apontador');
    if (!in_array($papelNovo, CONTA_PAPEIS, true)) {
        contaJson(['ok' => false, 'msg' => 'Papel inválido.'], 400);
    }

    // Função liberada pelo administrativo? Respeita o limite de acessos ativos.
    $config = contaFuncConfig($mysqli, $contaId);
    if (!$config) {
        contaJson(['ok' => false, 'msg' => 'A criação de usuários não está liberada para esta conta. Contate o administrativo.'], 403);
    }
    $limite = (int)$config['limite'];
    if (contaFuncContarAtivos($mysqli, $contaId) >= $limite) {
        contaJson(['ok' => false, 'msg' => "Limite de {$limite} acesso(s) ativo(s) atingido. Desative um acesso ou solicite aumento ao administrativo."], 400);
    }

    try {
        $novoId = usuarioCriarLocal($mysqli, [
            'nome'        => $_POST['nome'] ?? '',
            'login'       => $_POST['login'] ?? '',
            'email'       => $_POST['email'] ?? '',
            'senha'       => $_POST['senha'] ?? '',
            'conta_pai'   => $contaId,
            'papel_conta' => $papelNovo,
        ], $funcId > 0 ? $funcId : $contaId);
    } catch (InvalidArgumentException $e) {
        contaJson(['ok' => false, 'msg' => $e->getMessage()], 400);
    } catch (Throwable $e) {
        contaJson(['ok' => false, 'msg' => caderno_erro_msg($e)], 500);
    }

    contaJson(['ok' => true, 'msg' => 'Usuário criado com sucesso.', 'id' => $novoId]);
}

/* ============================================================
 * ATUALIZAR funcionário (nome, e-mail, papel, ativo)
 * ============================================================ */
if ($acao === 'atualizar') {
    $targetId = (int)($_POST['user_id'] ?? 0);
    $target = $targetId > 0 ? contaBuscarFuncionario($mysqli, $targetId, $contaId) : null;
    if (!$target) {
        contaJson(['ok' => false, 'msg' => 'Usuário não encontrado nesta conta.'], 404);
    }

    $sets = [];
    $tipos = '';
    $vals = [];

    if (isset($_POST['nome'])) {
        $nome = trim((string)$_POST['nome']);
        if ($nome === '') contaJson(['ok' => false, 'msg' => 'Nome não pode ficar vazio.'], 400);
        $sets[] = 'nome = ?';
        $tipos .= 's';
        $vals[] = $nome;
    }

    if (isset($_POST['email'])) {
        $email = strtolower(trim((string)$_POST['email']));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            contaJson(['ok' => false, 'msg' => 'E-mail inválido.'], 400);
        }
        $sets[] = 'email = ?';
        $tipos .= 's';
        $vals[] = $email !== '' ? $email : null;
    }

    if (isset($_POST['papel_conta'])) {
        $papelNovo = (string)$_POST['papel_conta'];
        if (!in_array($papelNovo, CONTA_PAPEIS, true)) {
            contaJson(['ok' => false, 'msg' => 'Papel inválido.'], 400);
        }
        // funcionário admin não pode rebaixar a si mesmo (evita perder a gestão sem querer)
        if ($funcId > 0 && $targetId === $funcId && $papelNovo !== 'admin') {
            contaJson(['ok' => false, 'msg' => 'Você não pode remover seu próprio papel de administrador. Peça ao dono da conta.'], 400);
        }
        $sets[] = 'papel_conta = ?';
        $tipos .= 's';
        $vals[] = $papelNovo;
    }

    if (isset($_POST['ativo'])) {
        $ativo = (int)$_POST['ativo'] === 1 ? 1 : 0;
        if ($funcId > 0 && $targetId === $funcId && $ativo === 0) {
            contaJson(['ok' => false, 'msg' => 'Você não pode desativar o seu próprio acesso.'], 400);
        }
        // Reativar um acesso também exige liberação e respeita o limite
        if ($ativo === 1 && (int)$target['ativo'] !== 1) {
            $config = contaFuncConfig($mysqli, $contaId);
            if (!$config) {
                contaJson(['ok' => false, 'msg' => 'A criação de usuários não está liberada para esta conta. Contate o administrativo.'], 403);
            }
            $limite = (int)$config['limite'];
            if (contaFuncContarAtivos($mysqli, $contaId) >= $limite) {
                contaJson(['ok' => false, 'msg' => "Limite de {$limite} acesso(s) ativo(s) atingido. Desative um acesso ou solicite aumento ao administrativo."], 400);
            }
        }
        $sets[] = 'ativo = ?';
        $tipos .= 'i';
        $vals[] = $ativo;
    }

    if (!$sets) {
        contaJson(['ok' => false, 'msg' => 'Nada para atualizar.'], 400);
    }

    $tipos .= 'i';
    $vals[] = $targetId;
    $stmt = $mysqli->prepare('UPDATE usuarios_caderno SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $stmt->bind_param($tipos, ...$vals);
    $stmt->execute();
    $stmt->close();

    contaJson(['ok' => true, 'msg' => 'Usuário atualizado.']);
}

contaJson(['ok' => false, 'msg' => 'Ação inválida.'], 400);
