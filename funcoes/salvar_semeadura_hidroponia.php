<?php
declare(strict_types=1);

require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/hidroponia_helpers.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

try {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) {
        throw new RuntimeException('Usuário não autenticado');
    }

    $stmt = $mysqli->prepare('SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $prop = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$prop) {
        throw new RuntimeException('Nenhuma propriedade ativa encontrada');
    }
    $propriedade_id = (int) $prop['id'];

    $estufa_id = (int) ($_POST['estufa_id'] ?? 0);
    $bancada_nome = trim((string) ($_POST['area_id'] ?? ''));
    $data = trim((string) ($_POST['data'] ?? date('Y-m-d')));
    $variedade = trim((string) ($_POST['variedade'] ?? ''));
    $tipoSemeadura = trim((string) ($_POST['tipo_semeadura'] ?? ''));
    $quantidade = trim((string) ($_POST['quantidade'] ?? ''));
    $unidade = trim((string) ($_POST['unidade'] ?? 'sementes'));
    $obs = trim((string) ($_POST['obs'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? ''));

    $tiposValidos = ['Direta', 'Bandeja', 'Canteiro', 'Replantio'];

    if (!$estufa_id || $bancada_nome === '') {
        throw new RuntimeException('Bancada ou estufa não identificada');
    }
    if ($data === '' || $quantidade === '' || !in_array($tipoSemeadura, $tiposValidos, true)) {
        throw new RuntimeException('Preencha data, quantidade e tipo de semeadura');
    }
    if (!in_array($status, ['pendente', 'concluido'], true)) {
        throw new RuntimeException('Selecione se o manejo está concluído ou pendente');
    }

    $stmt = $mysqli->prepare('
        SELECT id AS bancada_id, area_id, produto_id
        FROM bancadas
        WHERE estufa_id = ? AND nome LIKE CONCAT(\'%\', ?, \'%\')
        LIMIT 1
    ');
    $stmt->bind_param('is', $estufa_id, $bancada_nome);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) {
        throw new RuntimeException('Bancada não encontrada');
    }

    $area_id_real = $res['area_id'];
    $produto_id_real = (int) $res['produto_id'];

    $cultivo_produto_id = (int) ($_POST['cultivo_produto_id'] ?? 0);
    if ($cultivo_produto_id > 0) {
        $produtos_bancada = hidroponiaListarProdutosBancada(
            $mysqli,
            (int) ($res['bancada_id'] ?? 0),
            $produto_id_real
        );
        $ids_ok = array_column($produtos_bancada, 'id');
        if (in_array($cultivo_produto_id, $ids_ok, true)) {
            $produto_id_real = $cultivo_produto_id;
        }
    }

    $qtd = (float) str_replace(',', '.', $quantidade);
    if ($qtd <= 0) {
        throw new RuntimeException('Quantidade inválida');
    }

    $mysqli->begin_transaction();

    $tipo_apontamento = 'semeadura';

    if ($status === 'concluido') {
        $stmt = $mysqli->prepare('
            INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, unidade, observacoes, status, data_conclusao)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->bind_param(
            'issdsss',
            $propriedade_id,
            $tipo_apontamento,
            $data,
            $qtd,
            $unidade,
            $obs,
            $status
        );
    } else {
        $stmt = $mysqli->prepare('
            INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, unidade, observacoes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->bind_param(
            'issdsss',
            $propriedade_id,
            $tipo_apontamento,
            $data,
            $qtd,
            $unidade,
            $obs,
            $status
        );
    }
    $stmt->execute();
    $apontamento_id = (int) $stmt->insert_id;
    $stmt->close();

    if (!$apontamento_id) {
        throw new RuntimeException('Falha ao criar apontamento');
    }

    $detalhes = [
        'area_id' => (string) $area_id_real,
        'produto_id' => (string) $produto_id_real,
        'bancada_nome' => $bancada_nome,
    ];
    if ($variedade !== '') {
        $detalhes['variedade'] = $variedade;
    }
    if ($tipoSemeadura !== '') {
        $detalhes['tipo_semeadura'] = $tipoSemeadura;
    }

    $stmtDet = $mysqli->prepare('
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
        VALUES (?, ?, ?)
    ');
    foreach ($detalhes as $campo => $valor) {
        $stmtDet->bind_param('iss', $apontamento_id, $campo, $valor);
        $stmtDet->execute();
    }
    $stmtDet->close();

    $mysqli->commit();
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($mysqli->errno) {
        $mysqli->rollback();
    }
    echo json_encode(['ok' => false, 'err' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
