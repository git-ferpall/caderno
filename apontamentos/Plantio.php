<?php
// apontamentos/Plantio.php

class Plantio {

    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function salvar($dados) {
        try {
            $this->pdo->beginTransaction();

            // 1. Inserir cabeçalho do apontamento
            $sql = "INSERT INTO apontamentos (propriedade_id, tipo, data, status, obs) 
                    VALUES (:prop, 'plantio', :data, 'pendente', :obs)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':prop' => $dados['propriedade_id'],
                ':data' => $dados['data'],
                ':obs'  => $dados['obs'] ?? null
            ]);
            $apontamentoId = $this->pdo->lastInsertId();

            // 2. Inserir áreas
            if (!empty($dados['areas']) && is_array($dados['areas'])) {
                $stmtArea = $this->pdo->prepare("
                    INSERT INTO apontamento_areas (apontamento_id, area_id) 
                    VALUES (:apont, :area)
                ");
                foreach ($dados['areas'] as $areaId) {
                    $stmtArea->execute([
                        ':apont' => $apontamentoId,
                        ':area'  => $areaId
                    ]);
                }
            }

            // 3. Inserir produtos
            if (!empty($dados['produtos']) && is_array($dados['produtos'])) {
                $stmtProd = $this->pdo->prepare("
                    INSERT INTO apontamento_produtos 
                    (apontamento_id, produto_id, quantidade, previsao_colheita) 
                    VALUES (:apont, :produto, :qtd, :prev)
                ");
                foreach ($dados['produtos'] as $produto) {
                    $stmtProd->execute([
                        ':apont'   => $apontamentoId,
                        ':produto' => $produto['id'],
                        ':qtd'     => $produto['quantidade'] ?? null,
                        ':prev'    => $produto['previsao_colheita'] ?? null
                    ]);
                }
            }

            $this->pdo->commit();
            return ['ok' => true, 'id' => $apontamentoId];

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }
}
