<?php

function garantirTabelaApontamentoHistorico($mysqli): void
{
    $mysqli->query("
        CREATE TABLE IF NOT EXISTS apontamento_historico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            apontamento_id INT NOT NULL,
            user_id INT NOT NULL,
            campo VARCHAR(100) NOT NULL,
            valor_anterior TEXT NULL,
            valor_novo TEXT NULL,
            alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_apontamento_historico_apontamento (apontamento_id),
            INDEX idx_apontamento_historico_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function registrarHistoricoApontamento(
    $mysqli,
    int $apontamento_id,
    int $user_id,
    string $campo,
    $valor_anterior,
    $valor_novo
): void {
    $ant = ($valor_anterior === null || $valor_anterior === '') ? '' : (string)$valor_anterior;
    $nov = ($valor_novo === null || $valor_novo === '') ? '' : (string)$valor_novo;

    if ($ant === $nov) {
        return;
    }

    $stmt = $mysqli->prepare("
        INSERT INTO apontamento_historico (apontamento_id, user_id, campo, valor_anterior, valor_novo)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisss", $apontamento_id, $user_id, $campo, $ant, $nov);
    $stmt->execute();
    $stmt->close();
}

function labelCampoApontamento(string $campo): string
{
    $mapa = [
        'data' => 'Data',
        'observacoes' => 'Observações',
        'quantidade' => 'Quantidade',
        'unidade' => 'Unidade',
    ];
    return $mapa[$campo] ?? ucfirst(str_replace('_', ' ', $campo));
}
