<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

session_start();

try {
    // === Autenticação ===
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }

    if (!$user_id) {
        throw new Exception("Usuário não autenticado.");
    }

    // === Coleta filtros do POST ===
    $propriedades = $_POST['propriedades'] ?? [];
    $cultivo = $_POST['cultivo'] ?? '';
    $area = $_POST['area'] ?? '';
    $manejo = $_POST['manejo'] ?? '';
    $data_ini = $_POST['data_ini'] ?? date('Y-m-01');
    $data_fim = $_POST['data_fim'] ?? date('Y-m-t');

    // === Monta a query dinâmica ===
    $sql = "
        SELECT 
            a.id,
            a.tipo,
            a.data,
            a.status,
            a.observacoes,
            ar.nome AS area_nome,
            p.nome AS produto_nome,
            prop.nome_razao AS propriedade_nome
        FROM apontamentos a
        LEFT JOIN apontamento_detalhes ad_area ON ad_area.apontamento_id = a.id AND ad_area.campo = 'area_id'
        LEFT JOIN areas ar ON ar.id = ad_area.valor
        LEFT JOIN apontamento_detalhes ad_prod ON ad_prod.apontamento_id = a.id AND ad_prod.campo = 'produto_id'
        LEFT JOIN produtos p ON p.id = ad_prod.valor
        LEFT JOIN propriedades prop ON prop.id = a.propriedade_id
        WHERE a.data BETWEEN ? AND ?
          AND a.propriedade_id IN (" . str_repeat('?,', count($propriedades) - 1) . "?)
    ";

    $params = [$data_ini, $data_fim];
    $types = "ss";

    foreach ($propriedades as $pid) {
        $params[] = $pid;
        $types .= "i";
    }

    if ($cultivo) {
        $sql .= " AND p.nome LIKE ?";
        $params[] = "%$cultivo%";
        $types .= "s";
    }

    if ($area) {
        $sql .= " AND ar.nome LIKE ?";
        $params[] = "%$area%";
        $types .= "s";
    }

    if ($manejo) {
        $sql .= " AND a.tipo LIKE ?";
        $params[] = "%$manejo%";
        $types .= "s";
    }

    $sql .= " ORDER BY a.data DESC";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta SQL: " . $mysqli->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $dados = [];
    while ($row = $res->fetch_assoc()) {
        $dados[] = $row;
    }
    $stmt->close();

    // === Gera o PDF ===
    $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
    $mpdf->SetTitle("Relatório de Manejos - Frutag");

    // Cabeçalho e CSS básico
    $html = '
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { text-align: center; color: #2e7d32; margin-bottom: 5px; }
        h2 { text-align: center; font-size: 14px; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #4caf50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .footer { font-size: 10px; text-align: center; color: #777; margin-top: 20px; }
    </style>
    ';

    $html .= '<h1>Relatório de Manejos</h1>';
    $html .= '<h2>Período: ' . date('d/m/Y', strtotime($data_ini)) . ' a ' . date('d/m/Y', strtotime($data_fim)) . '</h2>';

    if (empty($dados)) {
        $html .= '<p style="text-align:center; margin-top:40px;">Nenhum registro encontrado para os filtros selecionados.</p>';
    } else {
        $html .= '<table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Propriedade</th>
                            <th>Área</th>
                            <th>Produto</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>';
        foreach ($dados as $d) {
            $html .= '<tr>
                        <td>' . date('d/m/Y', strtotime($d['data'])) . '</td>
                        <td>' . htmlspecialchars($d['propriedade_nome'] ?? '—') . '</td>
                        <td>' . htmlspecialchars($d['area_nome'] ?? '—') . '</td>
                        <td>' . htmlspecialchars($d['produto_nome'] ?? '—') . '</td>
                        <td>' . ucfirst(htmlspecialchars($d['tipo'] ?? '—')) . '</td>
                        <td>' . ucfirst(htmlspecialchars($d['status'] ?? '—')) . '</td>
                        <td>' . htmlspecialchars($d['observacoes'] ?? '—') . '</td>
                    </tr>';
        }
        $html .= '</tbody></table>';
    }

    $html .= '<div class="footer">Gerado em ' . date('d/m/Y H:i') . ' - Frutag Caderno de Campo</div>';

    // Gera PDF inline
    $mpdf->WriteHTML($html);
    $mpdf->Output('relatorio.pdf', Destination::INLINE);
} catch (Exception $e) {
    echo "<pre>Erro: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
