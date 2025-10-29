<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../../scripts/php/mpdf/vendor/autoload.php';

header('Content-Type: application/pdf; charset=utf-8');
session_start();

try {
    // === Autenticação ===
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) {
        throw new Exception('Usuário não autenticado.');
    }

    // === Dados recebidos do formulário ===
    $propriedades = $_POST['pfpropriedades'] ?? [];
    $area = $_POST['pfarea'] ?? '';
    $cultivo = $_POST['pfcult'] ?? '';
    $manejo = $_POST['pfmane'] ?? '';
    $data_ini = $_POST['pfini'] ?? '';
    $data_fin = $_POST['pffin'] ?? '';

    if (empty($propriedades)) {
        throw new Exception("Nenhuma propriedade selecionada.");
    }

    // === Monta lista para IN() ===
    $prop_ids = implode(',', array_map('intval', $propriedades));

    // === Query base ===
    $sql = "
        SELECT 
            a.id,
            a.tipo,
            a.data,
            a.quantidade,
            a.observacoes,
            a.status,
            p.nome_razao AS propriedade,
            GROUP_CONCAT(DISTINCT ar.nome SEPARATOR ', ') AS areas,
            (
                SELECT pr.nome
                FROM apontamento_detalhes ad2
                JOIN produtos pr ON pr.id = ad2.valor
                WHERE ad2.apontamento_id = a.id 
                AND (ad2.campo = 'produto' OR ad2.campo = 'produto_id')
                LIMIT 1
            ) AS produto
        FROM apontamentos a
        LEFT JOIN propriedades p ON p.id = a.propriedade_id
        LEFT JOIN apontamento_detalhes ad ON ad.apontamento_id = a.id AND ad.campo = 'area_id'
        LEFT JOIN areas ar ON ar.id = ad.valor
        WHERE a.propriedade_id IN ($prop_ids)
          AND a.data BETWEEN ? AND ?
    ";

    // === Filtros adicionais ===
    $params = [$data_ini, $data_fin];
    $types = "ss";

    if ($area !== '') {
        $sql .= " AND ar.nome = ? ";
        $params[] = $area;
        $types .= "s";
    }

    if ($cultivo !== '') {
        $sql .= " AND EXISTS (
            SELECT 1 FROM apontamento_detalhes ad3
            JOIN produtos pr2 ON pr2.id = ad3.valor
            WHERE ad3.apontamento_id = a.id
              AND (ad3.campo = 'produto' OR ad3.campo = 'produto_id')
              AND pr2.nome = ?
        ) ";
        $params[] = $cultivo;
        $types .= "s";
    }

    if ($manejo !== '') {
        $sql .= " AND a.tipo = ? ";
        $params[] = $manejo;
        $types .= "s";
    }

    $sql .= " GROUP BY a.id ORDER BY a.data DESC";

    // === Executa consulta ===
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    // === Gera HTML ===
    $html = '
    <h2 style="text-align:center;">Relatório de Manejos</h2>
    <p style="text-align:center;">Período: ' . date("d/m/Y", strtotime($data_ini)) . ' a ' . date("d/m/Y", strtotime($data_fin)) . '</p>
    <br><table border="1" cellspacing="0" cellpadding="6" width="100%">
        <thead>
            <tr style="background:#f2f2f2;">
                <th>ID</th>
                <th>Data</th>
                <th>Propriedade</th>
                <th>Área(s)</th>
                <th>Cultivo</th>
                <th>Tipo de manejo</th>
                <th>Quantidade</th>
                <th>Status</th>
                <th>Observações</th>
            </tr>
        </thead>
        <tbody>
    ';

    if ($res->num_rows === 0) {
        $html .= '<tr><td colspan="9" align="center">Nenhum registro encontrado para os filtros aplicados.</td></tr>';
    } else {
        while ($row = $res->fetch_assoc()) {
            $html .= "
                <tr>
                    <td>{$row['id']}</td>
                    <td>" . date('d/m/Y', strtotime($row['data'])) . "</td>
                    <td>{$row['propriedade']}</td>
                    <td>{$row['areas']}</td>
                    <td>{$row['produto']}</td>
                    <td>" . ucfirst($row['tipo']) . "</td>
                    <td>{$row['quantidade']}</td>
                    <td>{$row['status']}</td>
                    <td>{$row['observacoes']}</td>
                </tr>
            ";
        }
    }

    $html .= '</tbody></table>';

    // === Gera PDF ===
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L']);
    $mpdf->SetTitle("Relatório de Manejos");
    $mpdf->WriteHTML($html);
    $mpdf->Output("relatorio_manejos.pdf", "I");

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
