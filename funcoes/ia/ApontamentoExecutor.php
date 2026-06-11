<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../apontamento_arquivos.php';
require_once __DIR__ . '/contexto_usuario.php';
require_once __DIR__ . '/resolver_entidades.php';
require_once __DIR__ . '/consultas.php';

class ApontamentoExecutor
{
    public function __construct(
        private mysqli $mysqli,
        private int $user_id
    ) {}

    public function executar(array $intent, array $resolucao): array
    {
        $acao = $intent['acao'] ?? 'desconhecido';

        return match ($acao) {
            'criar_apontamento' => $this->criar($intent, $resolucao),
            'concluir_apontamento' => $this->concluir($intent, $resolucao),
            'cancelar_apontamento' => $this->cancelar($intent),
            'editar_apontamento' => $this->editar($intent),
            'listar_pendentes' => $this->listarPendentes(),
            'consultar' => $this->consultar($intent),
            default => [
                'ok' => false,
                'executado' => false,
                'msg' => $intent['mensagem'] ?? 'Não entendi o comando.',
            ],
        };
    }

    private function propriedadeId(): int
    {
        $prop = obterPropriedadeAtiva($this->mysqli, $this->user_id);
        if (!$prop) {
            throw new RuntimeException('Nenhuma propriedade ativa encontrada.');
        }
        return (int) $prop['id'];
    }

    private function criar(array $intent, array $resolucao): array
    {
        $tipo = (string) ($intent['tipo'] ?? '');
        $areaIds = $resolucao['area_ids'] ?? [];
        $produtoIds = $resolucao['produto_ids'] ?? [];

        if (!$areaIds) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe a área do manejo.'];
        }

        return match ($tipo) {
            'irrigacao' => $this->criarIrrigacao($intent, $areaIds, $produtoIds),
            'colheita' => $this->criarColheita($intent, $areaIds, $produtoIds),
            'semeadura' => $this->criarSemeadura($intent, $areaIds, $produtoIds),
            'plantio' => $this->criarPlantio($intent, $areaIds, $produtoIds),
            'herbicida', 'fungicida', 'inseticida', 'fertilizante' => $this->criarInsumo($intent, $areaIds, $tipo),
            'personalizado' => $this->criarPersonalizado($intent, $areaIds),
            default => [
                'ok' => false,
                'executado' => false,
                'msg' => 'Tipo de manejo não suportado pelo assistente: ' . ($tipo ?: 'indefinido'),
            ],
        };
    }

    private function criarIrrigacao(array $intent, array $areaIds, array $produtoIds): array
    {
        if (!$produtoIds) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe o produto/cultura da irrigação.'];
        }

        $volume = $intent['quantidade'] ?? null;
        if ($volume === null || !is_numeric($volume) || (float) $volume <= 0) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe o volume irrigado (litros ou m³).'];
        }

        $unidade = $this->mapUnidadeVolume((string) ($intent['unidade'] ?? 'litros'));
        $data = (string) ($intent['data'] ?? date('Y-m-d'));
        $obs = trim((string) ($intent['observacoes'] ?? ''));
        $status = ($data < date('Y-m-d')) ? 'concluido' : 'pendente';
        $propriedade_id = $this->propriedadeId();

        $this->mysqli->begin_transaction();
        try {
            $stmt = $this->mysqli->prepare('
                INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, unidade, observacoes, status)
                VALUES (?, \'irrigacao\', ?, ?, ?, ?, ?)
            ');
            $vol = (float) $volume;
            $stmt->bind_param('isdsss', $propriedade_id, $data, $vol, $unidade, $obs, $status);
            $stmt->execute();
            $apontamento_id = (int) $stmt->insert_id;
            $stmt->close();

            $this->inserirDetalhes($apontamento_id, $areaIds, $produtoIds, 'irrigacao');

            if (!empty($intent['tempo_irrigacao'])) {
                $this->inserirDetalhe($apontamento_id, 'tempo_irrigacao', (string) $intent['tempo_irrigacao']);
                $ut = $this->mapUnidadeTempo((string) ($intent['unidade_tempo'] ?? 'horas'));
                $this->inserirDetalhe($apontamento_id, 'tempo_unidade', $ut);
            }

            $this->mysqli->commit();
            return [
                'ok' => true,
                'executado' => true,
                'msg' => 'Irrigação registrada com sucesso!',
                'apontamento_id' => $apontamento_id,
            ];
        } catch (Throwable $e) {
            $this->mysqli->rollback();
            throw $e;
        }
    }

    private function criarColheita(array $intent, array $areaIds, array $produtoIds): array
    {
        if (!$produtoIds) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe o produto da colheita.'];
        }

        $quantidade = $intent['quantidade'] ?? null;
        if ($quantidade === null || !is_numeric($quantidade)) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe a quantidade colhida.'];
        }

        $unidade = trim((string) ($intent['unidade'] ?? 'kg'));
        if ($unidade === '') {
            $unidade = 'kg';
        }

        $data = (string) ($intent['data'] ?? date('Y-m-d'));
        $obs = trim((string) ($intent['observacoes'] ?? ''));
        $qtd = (float) $quantidade;
        $status = $qtd > 0 ? 'concluido' : 'pendente';
        $propriedade_id = $this->propriedadeId();

        $this->mysqli->begin_transaction();
        try {
            $stmt = $this->mysqli->prepare('
                INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, unidade, observacoes, status)
                VALUES (?, \'colheita\', ?, ?, ?, ?, ?)
            ');
            $stmt->bind_param('isdsss', $propriedade_id, $data, $qtd, $unidade, $obs, $status);
            $stmt->execute();
            $apontamento_id = (int) $stmt->insert_id;
            $stmt->close();

            $this->inserirDetalhes($apontamento_id, $areaIds, $produtoIds, 'colheita');
            $this->mysqli->commit();

            return [
                'ok' => true,
                'executado' => true,
                'msg' => 'Colheita registrada com sucesso!',
                'apontamento_id' => $apontamento_id,
            ];
        } catch (Throwable $e) {
            $this->mysqli->rollback();
            throw $e;
        }
    }

    private function criarSemeadura(array $intent, array $areaIds, array $produtoIds): array
    {
        if (!$produtoIds) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe o produto/cultura da semeadura.'];
        }

        $tipoSemeadura = (string) ($intent['tipo_semeadura'] ?? '');
        $tiposValidos = ['Direta', 'Bandeja', 'Canteiro', 'Replantio'];
        if (!in_array($tipoSemeadura, $tiposValidos, true)) {
            $tipoSemeadura = 'Direta';
        }

        $quantidade = $intent['quantidade'] ?? null;
        if ($quantidade === null || !is_numeric($quantidade) || (float) $quantidade <= 0) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe a quantidade semeada.'];
        }

        $unidade = trim((string) ($intent['unidade'] ?? 'sementes'));
        if ($unidade === '') {
            $unidade = 'sementes';
        }

        $data = (string) ($intent['data'] ?? date('Y-m-d'));
        $obs = trim((string) ($intent['observacoes'] ?? ''));
        $variedade = trim((string) ($intent['variedade'] ?? ''));
        $propriedade_id = $this->propriedadeId();
        $qtd = (float) $quantidade;
        $status = in_array($intent['status'] ?? '', ['pendente', 'concluido'], true)
            ? $intent['status']
            : 'concluido';

        $this->mysqli->begin_transaction();
        try {
            if ($status === 'concluido') {
                $stmt = $this->mysqli->prepare('
                    INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, unidade, observacoes, status, data_conclusao)
                    VALUES (?, \'semeadura\', ?, ?, ?, ?, ?, NOW())
                ');
            } else {
                $stmt = $this->mysqli->prepare('
                    INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, unidade, observacoes, status)
                    VALUES (?, \'semeadura\', ?, ?, ?, ?, ?)
                ');
            }
            $stmt->bind_param('isdsss', $propriedade_id, $data, $qtd, $unidade, $obs, $status);
            $stmt->execute();
            $apontamento_id = (int) $stmt->insert_id;
            $stmt->close();

            $this->inserirDetalhes($apontamento_id, $areaIds, $produtoIds, 'semeadura');

            if ($variedade !== '') {
                $this->inserirDetalhe($apontamento_id, 'variedade', $variedade);
            }
            $this->inserirDetalhe($apontamento_id, 'tipo_semeadura', $tipoSemeadura);

            $this->mysqli->commit();
            return [
                'ok' => true,
                'executado' => true,
                'msg' => 'Semeadura registrada com sucesso!',
                'apontamento_id' => $apontamento_id,
            ];
        } catch (Throwable $e) {
            $this->mysqli->rollback();
            throw $e;
        }
    }

    private function criarPlantio(array $intent, array $areaIds, array $produtoIds): array
    {
        if (!$produtoIds) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe o produto/cultura do plantio.'];
        }

        $quantidade = $intent['quantidade'] ?? null;
        if ($quantidade === null || !is_numeric($quantidade) || (float) $quantidade <= 0) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe a quantidade do plantio.'];
        }

        $unidade = trim((string) ($intent['unidade'] ?? 'mudas'));
        if ($unidade === '') {
            $unidade = 'mudas';
        }

        $data = (string) ($intent['data'] ?? date('Y-m-d'));
        $obs = trim((string) ($intent['observacoes'] ?? ''));
        $previsaoDias = $intent['previsao_dias'] ?? null;
        $propriedade_id = $this->propriedadeId();
        $qtd = (float) $quantidade;
        $status = 'pendente';

        $dataColheita = null;
        if ($previsaoDias !== null && is_numeric($previsaoDias) && (int) $previsaoDias > 0) {
            $dataColheita = (new DateTime($data))->modify('+' . (int) $previsaoDias . ' days')->format('Y-m-d');
        }

        $this->mysqli->begin_transaction();
        try {
            $stmt = $this->mysqli->prepare('
                INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, unidade, observacoes, status)
                VALUES (?, \'plantio\', ?, ?, ?, ?, ?)
            ');
            $stmt->bind_param('isdsss', $propriedade_id, $data, $qtd, $unidade, $obs, $status);
            $stmt->execute();
            $plantio_id = (int) $stmt->insert_id;
            $stmt->close();

            $this->inserirDetalhes($plantio_id, $areaIds, $produtoIds, 'plantio');

            if ($dataColheita) {
                $obsColheita = 'Gerado automaticamente pelo plantio #' . $plantio_id;
                $quantidadeCol = 0.0;
                $tipoColheita = 'colheita';

                $stmt = $this->mysqli->prepare('
                    INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                $stmt->bind_param('issdss', $propriedade_id, $tipoColheita, $dataColheita, $quantidadeCol, $obsColheita, $status);
                $stmt->execute();
                $colheita_id = (int) $stmt->insert_id;
                $stmt->close();

                $this->inserirDetalhes($colheita_id, $areaIds, $produtoIds, 'colheita');
            }

            $this->mysqli->commit();
            $msg = 'Plantio registrado com sucesso!';
            if ($dataColheita) {
                $msg .= ' Colheita prevista para ' . date('d/m/Y', strtotime($dataColheita)) . '.';
            }

            return [
                'ok' => true,
                'executado' => true,
                'msg' => $msg,
                'apontamento_id' => $plantio_id,
            ];
        } catch (Throwable $e) {
            $this->mysqli->rollback();
            throw $e;
        }
    }

    private function criarInsumo(array $intent, array $areaIds, string $tipo): array
    {
        $nome = trim((string) ($intent['insumo_nome'] ?? ''));
        if ($nome === '') {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe qual produto foi aplicado.'];
        }

        $quantidade = $intent['quantidade'] ?? null;
        if ($quantidade === null || !is_numeric($quantidade) || (float) $quantidade <= 0) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe a quantidade aplicada.'];
        }

        $unidade = trim((string) ($intent['unidade'] ?? 'litros'));
        if ($unidade === '') {
            $unidade = 'litros';
        }

        $data = (string) ($intent['data'] ?? date('Y-m-d'));
        $obs = trim((string) ($intent['observacoes'] ?? ''));
        $propriedade_id = $this->propriedadeId();
        $qtd = (float) $quantidade;
        $status = 'pendente';

        $this->mysqli->begin_transaction();
        try {
            $stmt = $this->mysqli->prepare('
                INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, unidade, observacoes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->bind_param('issdsss', $propriedade_id, $tipo, $data, $qtd, $unidade, $obs, $status);
            $stmt->execute();
            $apontamento_id = (int) $stmt->insert_id;
            $stmt->close();

            foreach ($areaIds as $area_id) {
                $this->inserirDetalhe($apontamento_id, 'area_id', (string) $area_id);
            }
            $this->inserirDetalhe($apontamento_id, $tipo, $nome);

            $this->mysqli->commit();

            $rotulo = ucfirst($tipo);
            return [
                'ok' => true,
                'executado' => true,
                'msg' => "{$rotulo} registrado com sucesso!",
                'apontamento_id' => $apontamento_id,
            ];
        } catch (Throwable $e) {
            $this->mysqli->rollback();
            throw $e;
        }
    }

    private function criarPersonalizado(array $intent, array $areaIds): array
    {
        $titulo = trim((string) ($intent['titulo'] ?? ''));
        if ($titulo === '') {
            $titulo = trim((string) ($intent['mensagem'] ?? 'Apontamento por voz'));
        }
        if ($titulo === '') {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe o título do apontamento.'];
        }

        $data = (string) ($intent['data'] ?? date('Y-m-d'));
        $descricao = trim((string) ($intent['descricao'] ?? $intent['observacoes'] ?? ''));
        $propriedade_id = $this->propriedadeId();
        $status = 'pendente';

        $this->mysqli->begin_transaction();
        try {
            $stmt = $this->mysqli->prepare('
                INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
                VALUES (?, \'personalizado\', ?, NULL, ?, ?)
            ');
            $stmt->bind_param('isss', $propriedade_id, $data, $descricao, $status);
            $stmt->execute();
            $apontamento_id = (int) $stmt->insert_id;
            $stmt->close();

            foreach ($areaIds as $area_id) {
                $this->inserirDetalhe($apontamento_id, 'area_id', (string) $area_id);
            }
            $this->inserirDetalhe($apontamento_id, 'titulo', $titulo);

            $this->mysqli->commit();
            return [
                'ok' => true,
                'executado' => true,
                'msg' => 'Apontamento personalizado criado!',
                'apontamento_id' => $apontamento_id,
            ];
        } catch (Throwable $e) {
            $this->mysqli->rollback();
            throw $e;
        }
    }

    private function concluir(array $intent, array $resolucao): array
    {
        $id = $intent['apontamento_id'] ?? null;
        if (!$id) {
            $id = $this->buscarPendentePorRef($intent, $resolucao);
        }

        if (!$id) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Não encontrei manejo pendente correspondente.'];
        }

        if (!apontamentoPertenceUsuario($this->mysqli, (int) $id, $this->user_id)) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Apontamento não encontrado.'];
        }

        $stmt = $this->mysqli->prepare('SELECT tipo FROM apontamentos WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Apontamento não encontrado.'];
        }

        $tipo = $row['tipo'];

        if ($tipo === 'colheita') {
            $quantidade = $intent['quantidade'] ?? null;
            if ($quantidade === null || !is_numeric($quantidade) || (float) $quantidade <= 0) {
                return [
                    'ok' => false,
                    'executado' => false,
                    'msg' => 'Para concluir colheita, informe a quantidade colhida.',
                    'precisa_quantidade' => true,
                ];
            }
            $unidade = trim((string) ($intent['unidade'] ?? 'kg'));
            $qtd = (float) $quantidade;
            $stmt = $this->mysqli->prepare('
                UPDATE apontamentos SET status = \'concluido\', quantidade = ?, unidade = ?, data_conclusao = NOW()
                WHERE id = ?
            ');
            $stmt->bind_param('dsi', $qtd, $unidade, $id);
        } else {
            $stmt = $this->mysqli->prepare('
                UPDATE apontamentos SET status = \'concluido\', data_conclusao = NOW() WHERE id = ?
            ');
            $stmt->bind_param('i', $id);
        }

        $stmt->execute();
        $stmt->close();

        return [
            'ok' => true,
            'executado' => true,
            'msg' => 'Manejo marcado como concluído!',
            'apontamento_id' => (int) $id,
        ];
    }

    private function listarPendentes(): array
    {
        $propId = $this->propriedadeId();
        return iaConsultaListarPendentes($this->mysqli, $propId);
    }

    private function consultar(array $intent): array
    {
        $contexto = iaContextoUsuario($this->mysqli, $this->user_id);
        return iaExecutarConsulta($this->mysqli, $this->user_id, $intent, $contexto);
    }

    private function cancelar(array $intent): array
    {
        $id = (int) ($intent['apontamento_id'] ?? 0);
        if ($id <= 0) {
            $propId = $this->propriedadeId();
            $stmt = $this->mysqli->prepare('SELECT id FROM apontamentos WHERE propriedade_id = ? ORDER BY id DESC LIMIT 1');
            $stmt->bind_param('i', $propId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $id = (int) ($row['id'] ?? 0);
        }

        if ($id <= 0) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Não encontrei apontamento para cancelar.'];
        }

        if (!apontamentoPertenceUsuario($this->mysqli, $id, $this->user_id)) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Apontamento não encontrado.'];
        }

        $this->mysqli->begin_transaction();
        try {
            $stmt = $this->mysqli->prepare('DELETE FROM apontamento_detalhes WHERE apontamento_id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $this->mysqli->prepare('DELETE FROM apontamentos WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $this->mysqli->commit();
            return [
                'ok' => true,
                'executado' => true,
                'msg' => 'Apontamento cancelado com sucesso.',
                'apontamento_id' => $id,
            ];
        } catch (Throwable $e) {
            $this->mysqli->rollback();
            throw $e;
        }
    }

    private function editar(array $intent): array
    {
        $id = (int) ($intent['apontamento_id'] ?? 0);
        if ($id <= 0) {
            $propId = $this->propriedadeId();
            $stmt = $this->mysqli->prepare('SELECT id FROM apontamentos WHERE propriedade_id = ? ORDER BY id DESC LIMIT 1');
            $stmt->bind_param('i', $propId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $id = (int) ($row['id'] ?? 0);
        }

        $obs = trim((string) ($intent['observacoes'] ?? ''));
        if ($obs === '') {
            return ['ok' => false, 'executado' => false, 'msg' => 'Informe a observação que deseja salvar.'];
        }

        if ($id <= 0 || !apontamentoPertenceUsuario($this->mysqli, $id, $this->user_id)) {
            return ['ok' => false, 'executado' => false, 'msg' => 'Apontamento não encontrado.'];
        }

        $stmt = $this->mysqli->prepare('UPDATE apontamentos SET observacoes = ? WHERE id = ?');
        $stmt->bind_param('si', $obs, $id);
        $stmt->execute();
        $stmt->close();

        return [
            'ok' => true,
            'executado' => true,
            'msg' => 'Observação atualizada no apontamento.',
            'apontamento_id' => $id,
        ];
    }

    private function buscarPendentePorRef(array $intent, array $resolucao): ?int
    {
        $ref = $intent['apontamento_ref'] ?? null;
        $tipo = $ref['tipo'] ?? $intent['tipo'] ?? null;
        $areaIds = $resolucao['area_ids'] ?? [];
        $produtoIds = $resolucao['produto_ids'] ?? [];
        $data = $ref['data'] ?? $intent['data'] ?? null;

        $propId = $this->propriedadeId();

        $sql = "
            SELECT a.id
            FROM apontamentos a
            WHERE a.propriedade_id = ? AND a.status = 'pendente'
        ";
        $types = 'i';
        $params = [$propId];

        if ($tipo) {
            $sql .= ' AND a.tipo = ?';
            $types .= 's';
            $params[] = $tipo;
        }
        if ($data) {
            $sql .= ' AND a.data = ?';
            $types .= 's';
            $params[] = $data;
        }

        $sql .= ' ORDER BY a.data DESC LIMIT 30';

        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        $candidatos = [];
        while ($row = $res->fetch_assoc()) {
            $candidatos[] = (int) $row['id'];
        }
        $stmt->close();

        if (!$candidatos) {
            return null;
        }

        if (count($candidatos) === 1 && !$areaIds && !$produtoIds) {
            return $candidatos[0];
        }

        foreach ($candidatos as $aptId) {
            if ($areaIds && !$this->apontamentoTemArea($aptId, $areaIds)) {
                continue;
            }
            if ($produtoIds && !$this->apontamentoTemProduto($aptId, $produtoIds)) {
                continue;
            }
            return $aptId;
        }

        return $candidatos[0] ?? null;
    }

    private function apontamentoTemArea(int $apontamento_id, array $areaIds): bool
    {
        $placeholders = implode(',', array_fill(0, count($areaIds), '?'));
        $sql = "SELECT 1 FROM apontamento_detalhes WHERE apontamento_id = ? AND campo = 'area_id' AND valor IN ($placeholders) LIMIT 1";
        $types = 'i' . str_repeat('i', count($areaIds));
        $params = array_merge([$apontamento_id], $areaIds);
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $ok = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $ok;
    }

    private function apontamentoTemProduto(int $apontamento_id, array $produtoIds): bool
    {
        $placeholders = implode(',', array_fill(0, count($produtoIds), '?'));
        $sql = "SELECT 1 FROM apontamento_detalhes WHERE apontamento_id = ? AND campo IN ('produto','produto_id') AND valor IN ($placeholders) LIMIT 1";
        $types = 'i' . str_repeat('i', count($produtoIds));
        $params = array_merge([$apontamento_id], $produtoIds);
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $ok = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $ok;
    }

    private function inserirDetalhes(int $apontamento_id, array $areaIds, array $produtoIds, string $tipo): void
    {
        foreach ($areaIds as $area_id) {
            $this->inserirDetalhe($apontamento_id, 'area_id', (string) $area_id);
        }

        $campoProduto = $tipo === 'irrigacao' ? 'produto' : 'produto_id';
        foreach ($produtoIds as $produto_id) {
            $this->inserirDetalhe($apontamento_id, $campoProduto, (string) $produto_id);
        }
    }

    private function inserirDetalhe(int $apontamento_id, string $campo, string $valor): void
    {
        $stmt = $this->mysqli->prepare('INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $apontamento_id, $campo, $valor);
        $stmt->execute();
        $stmt->close();
    }

    private function mapUnidadeVolume(string $u): string
    {
        $u = mb_strtolower(trim($u), 'UTF-8');
        return match (true) {
            str_contains($u, 'm3'), str_contains($u, 'm³'), $u === 'm' => 'm3',
            default => 'l',
        };
    }

    private function mapUnidadeTempo(string $u): string
    {
        $u = mb_strtolower(trim($u), 'UTF-8');
        return str_contains($u, 'min') ? 'min' : 'h';
    }
}
