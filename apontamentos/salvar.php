<?php
// apontamentos/salvar.php
require_once __DIR__ . '/../configuracao/conexao.php';
require_once __DIR__ . '/Plantio.php';

header('Content-Type: application/json; charset=utf-8');

$acao = $_POST['acao'] ?? '';

if ($acao === 'plantio') {
    $plantio = new Plantio($pdo);

    // Monta os dados vindos do form
    $dados = [
        'propriedade_id' => $_POST['propriedade_id'],
        'data'           => $_POST['data'],
        'obs'            => $_POST['obs'] ?? null,
        'areas'          => $_POST['areas'] ?? [],

        // Produtos enviados como array
        'produtos'       => []
    ];

    if (!empty($_POST['produtos']) && is_array($_POST['produtos'])) {
        foreach ($_POST['produtos'] as $p) {
            $dados['produtos'][] = [
                'id'               => $p['id'],
                'quantidade'       => $p['quantidade'] ?? null,
                'previsao_colheita'=> $p['previsao_colheita'] ?? null
            ];
        }
    }

    $res = $plantio->salvar($dados);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => false, 'erro' => 'Ação inválida']);
