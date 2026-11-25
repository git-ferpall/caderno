<?php

require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/apontamento_arquivos.php';

header('Content-Type: application/json; charset=utf-8');

$id         = $_POST['id'] ?? null;
$quantidade = $_POST['quantidade'] ?? null;
$unidade    = $_POST['unidade'] ?? null;

if (!$id || !is_numeric($id)) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
}

garantirColunaApontamentoExcluido($mysqli);

$mysqli->begin_transaction();

try {

    // 🔎 Primeiro buscamos o tipo do apontamento
    $stmt = $mysqli->prepare("
        SELECT tipo, excluido_em
        FROM apontamentos 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['ok' => false, 'msg' => 'Apontamento não encontrado']);
        exit;
    }

    if (!empty($row['excluido_em'])) {
        $mysqli->rollback();
        echo json_encode(['ok' => false, 'msg' => 'Apontamento excluído não pode ser concluído']);
        exit;
    }

    $tipo = $row['tipo'];

    // ==========================================================
    // 🌽 SE FOR COLHEITA → EXIGE QUANTIDADE
    // ==========================================================

    if ($tipo === 'colheita') {

        if ($quantidade === null || $quantidade === '' || !is_numeric($quantidade)) {
            echo json_encode(['ok' => false, 'msg' => 'Informe a quantidade colhida']);
            exit;
        }

        $quantidade = floatval($quantidade);

        $stmt = $mysqli->prepare("
            UPDATE apontamentos
            SET status = 'concluido',
                quantidade = ?,
                unidade = ?,
                data_conclusao = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("dsi", $quantidade, $unidade, $id);
    }

    // ==========================================================
    // 🌱 OUTROS TIPOS
    // ==========================================================

    else {

        $stmt = $mysqli->prepare("
            UPDATE apontamentos
            SET status = 'concluido',
                data_conclusao = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
    }

    $stmt->execute();

    if ($stmt->affected_rows >= 0) {

        $mysqli->commit();

        echo json_encode([
            'ok'  => true,
            'msg' => 'Manejo marcado como concluído com sucesso'
        ]);

    } else {

        $mysqli->rollback();

        echo json_encode([
            'ok'  => false,
            'msg' => 'Nenhum registro atualizado'
        ]);
    }

    $stmt->close();

} catch (Throwable $e) {

    $mysqli->rollback();

    echo json_encode([
        'ok'  => false,
        'msg' => 'Erro no servidor'
    ]);
}