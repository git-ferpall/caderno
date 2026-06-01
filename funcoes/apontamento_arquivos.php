<?php

function garantirTabelaApontamentoArquivos($mysqli): void
{
    $mysqli->query("
        CREATE TABLE IF NOT EXISTS apontamento_arquivos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            apontamento_id INT NOT NULL,
            silo_arquivo_id INT NOT NULL,
            user_id INT NOT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_apontamento_silo (apontamento_id, silo_arquivo_id),
            INDEX idx_apontamento_arquivos_apontamento (apontamento_id),
            INDEX idx_apontamento_arquivos_silo (silo_arquivo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function obterPropriedadeAtiva($mysqli, int $user_id): ?array
{
    $stmt = $mysqli->prepare("SELECT id, nome_razao FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function apontamentoPertenceUsuario($mysqli, int $apontamento_id, int $user_id): bool
{
    $stmt = $mysqli->prepare("
        SELECT a.id
        FROM apontamentos a
        INNER JOIN propriedades p ON p.id = a.propriedade_id
        WHERE a.id = ? AND p.user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $apontamento_id, $user_id);
    $stmt->execute();
    $ok = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $ok;
}

function listarArquivosApontamento($mysqli, int $apontamento_id, int $user_id): array
{
    garantirTabelaApontamentoArquivos($mysqli);

    $stmt = $mysqli->prepare("
        SELECT s.id, s.nome_arquivo, s.tipo_arquivo, s.tamanho_bytes, s.criado_em, aa.id AS vinculo_id
        FROM apontamento_arquivos aa
        INNER JOIN silo_arquivos s ON s.id = aa.silo_arquivo_id AND s.user_id = ?
        WHERE aa.apontamento_id = ? AND aa.user_id = ?
        ORDER BY aa.criado_em DESC
    ");
    $stmt->bind_param('iii', $user_id, $apontamento_id, $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function vincularArquivoApontamento($mysqli, int $apontamento_id, int $silo_arquivo_id, int $user_id): void
{
    garantirTabelaApontamentoArquivos($mysqli);

    if (!apontamentoPertenceUsuario($mysqli, $apontamento_id, $user_id)) {
        throw new InvalidArgumentException('apontamento_invalido');
    }

    $stmt = $mysqli->prepare("
        SELECT id FROM silo_arquivos
        WHERE id = ? AND user_id = ? AND tipo = 'arquivo'
        LIMIT 1
    ");
    $stmt->bind_param('ii', $silo_arquivo_id, $user_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        throw new InvalidArgumentException('arquivo_invalido');
    }
    $stmt->close();

    $stmt = $mysqli->prepare("
        INSERT IGNORE INTO apontamento_arquivos (apontamento_id, silo_arquivo_id, user_id)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param('iii', $apontamento_id, $silo_arquivo_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

function desvincularArquivoApontamento($mysqli, int $vinculo_id, int $user_id): void
{
    garantirTabelaApontamentoArquivos($mysqli);

    $stmt = $mysqli->prepare("DELETE FROM apontamento_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $vinculo_id, $user_id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        throw new InvalidArgumentException('vinculo_nao_encontrado');
    }
    $stmt->close();
}

function labelTipoApontamento(string $tipo): string
{
    $tipo = strtolower(trim($tipo));
    $mapa = [
        'herbicida' => 'Herbicida',
        'fungicida' => 'Fungicida',
        'inseticida' => 'Inseticida',
        'fertilizante' => 'Fertilizante',
        'colheita' => 'Colheita',
        'plantio' => 'Plantio',
        'irrigacao' => 'Irrigação',
        'manejo_integrado' => 'Manejo integrado',
        'moscas_frutas' => 'Moscas-das-frutas',
        'erradicacao' => 'Erradicação',
        'visita_tecnica' => 'Visita técnica',
        'pragas_doencas' => 'Pragas e doenças',
        'coleta_analise' => 'Coleta / análise',
        'controle_agua' => 'Controle de água',
        'adubacao_calcario' => 'Adubação / calcário',
        'adubacao_organica' => 'Adubação orgânica',
        'revisao_maquinas' => 'Revisão de máquinas',
        'transplantio' => 'Transplantio',
        'personalizado' => 'Personalizado',
        'clima' => 'Registro climático',
        'defensivo' => 'Defensivo',
    ];
    return $mapa[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
}

function iconeTipoApontamento(string $tipo): string
{
    $tipo = strtolower(trim($tipo));
    $mapa = [
        'colheita' => '🌾',
        'plantio' => '🌱',
        'irrigacao' => '💧',
        'herbicida' => '🧴',
        'fungicida' => '🧴',
        'inseticida' => '🧴',
        'fertilizante' => '🧪',
        'visita_tecnica' => '👨‍🌾',
        'clima' => '🌤️',
        'personalizado' => '📝',
    ];
    return $mapa[$tipo] ?? '📋';
}
