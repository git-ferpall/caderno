<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/pix_payload.php';

$user_id = frutibankRequireAcesso($mysqli);
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

/** Aceita "1.234,56", "1234,56" e "1234.56". */
function frutibankParseValor(string $v): float
{
    $v = trim($v);
    if (strpos($v, ',') !== false) {
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
    }
    return round((float)$v, 2);
}

function frutibankValidarCpfCnpj(string $doc): ?string
{
    $doc = preg_replace('/\D/', '', $doc) ?? '';

    if (strlen($doc) === 11) {
        if (preg_match('/^(\d)\1{10}$/', $doc)) return null;
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) $soma += (int)$doc[$i] * (($t + 1) - $i);
            $dig = ((10 * $soma) % 11) % 10;
            if ((int)$doc[$t] !== $dig) return null;
        }
        return $doc;
    }

    if (strlen($doc) === 14) {
        if (preg_match('/^(\d)\1{13}$/', $doc)) return null;
        $pesos1 = [5,4,3,2,9,8,7,6,5,4,3,2];
        $pesos2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        $soma = 0;
        foreach ($pesos1 as $i => $p) $soma += (int)$doc[$i] * $p;
        $d1 = $soma % 11 < 2 ? 0 : 11 - ($soma % 11);
        if ((int)$doc[12] !== $d1) return null;
        $soma = 0;
        foreach ($pesos2 as $i => $p) $soma += (int)$doc[$i] * $p;
        $d2 = $soma % 11 < 2 ? 0 : 11 - ($soma % 11);
        if ((int)$doc[13] !== $d2) return null;
        return $doc;
    }

    return null;
}

switch ($acao) {

    /* ---------------- Configuração (chave PIX) ---------------- */

    case 'get_config':
        frutibankJson(['ok' => true, 'config' => frutibankGetConfig($mysqli, $user_id)]);

    case 'salvar_config':
        $chave = trim($_POST['chave_pix'] ?? '');
        $tipo = $_POST['tipo_chave'] ?? 'aleatoria';
        $nome = trim($_POST['nome_recebedor'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $uf = strtoupper(trim($_POST['uf'] ?? ''));

        if ($chave === '' || strlen($chave) > 140) {
            frutibankJson(['ok' => false, 'msg' => 'Informe uma chave PIX válida.'], 400);
        }
        if (!in_array($tipo, ['cpf', 'cnpj', 'email', 'telefone', 'aleatoria'], true)) {
            frutibankJson(['ok' => false, 'msg' => 'Tipo de chave inválido.'], 400);
        }
        if ($nome === '' || $cidade === '') {
            frutibankJson(['ok' => false, 'msg' => 'Informe o nome do recebedor e a cidade (obrigatórios no padrão PIX).'], 400);
        }
        if (!preg_match('/^[A-Z]{2}$/', $uf)) {
            frutibankJson(['ok' => false, 'msg' => 'Selecione o estado (UF).'], 400);
        }

        $stmt = $mysqli->prepare("
            INSERT INTO frutibank_config (user_id, chave_pix, tipo_chave, nome_recebedor, cidade, uf)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                chave_pix = VALUES(chave_pix),
                tipo_chave = VALUES(tipo_chave),
                nome_recebedor = VALUES(nome_recebedor),
                cidade = VALUES(cidade),
                uf = VALUES(uf)
        ");
        $nome25 = mb_substr($nome, 0, 25);
        $cidade80 = mb_substr($cidade, 0, 80);
        $stmt->bind_param('isssss', $user_id, $chave, $tipo, $nome25, $cidade80, $uf);
        $stmt->execute();
        $stmt->close();
        frutibankJson(['ok' => true, 'msg' => 'Chave PIX salva com sucesso.']);

    /* ---------------- Consulta CNPJ (Receita Federal via BrasilAPI) ---------------- */

    case 'consultar_cnpj':
        $cnpj = preg_replace('/\D/', '', $_GET['cnpj'] ?? $_POST['cnpj'] ?? '') ?? '';
        if (strlen($cnpj) !== 14) {
            frutibankJson(['ok' => false, 'msg' => 'Informe um CNPJ completo (14 dígitos).'], 400);
        }
        if (frutibankValidarCpfCnpj($cnpj) === null) {
            frutibankJson(['ok' => false, 'msg' => 'CNPJ inválido. Confira os dígitos.'], 400);
        }

        $ch = curl_init('https://brasilapi.com.br/api/cnpj/v1/' . $cnpj);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'CadernoFrutag/1.0',
        ]);
        $resp = curl_exec($ch);
        $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpStatus === 404) {
            frutibankJson(['ok' => false, 'msg' => 'CNPJ não encontrado na Receita Federal.'], 404);
        }
        $dados = $resp ? json_decode($resp, true) : null;
        if ($httpStatus !== 200 || !is_array($dados) || empty($dados['razao_social'])) {
            frutibankJson(['ok' => false, 'msg' => 'Consulta à Receita indisponível no momento. Preencha os dados manualmente.'], 502);
        }

        frutibankJson(['ok' => true, 'dados' => [
            'razao_social'  => $dados['razao_social'],
            'nome_fantasia' => $dados['nome_fantasia'] ?? '',
            'situacao'      => $dados['descricao_situacao_cadastral'] ?? '',
            'municipio'     => $dados['municipio'] ?? '',
            'uf'            => $dados['uf'] ?? '',
        ]]);

    /* ---------------- Clientes de cobrança ---------------- */

    case 'listar_clientes':
        $q = trim($_GET['q'] ?? '');
        $sql = "SELECT c.id, c.nome, c.cpf_cnpj, c.criado_em,
                       (SELECT COUNT(*) FROM frutibank_cobrancas fc WHERE fc.cliente_id = c.id) AS total_cobrancas
                FROM frutibank_clientes c
                WHERE c.user_id = $user_id";
        if ($q !== '') {
            $like = '%' . $mysqli->real_escape_string($q) . '%';
            $sql .= " AND (c.nome LIKE '$like' OR c.cpf_cnpj LIKE '$like')";
        }
        $sql .= ' ORDER BY c.nome ASC LIMIT 300';
        $rows = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);
        frutibankJson(['ok' => true, 'clientes' => $rows]);

    case 'salvar_cliente':
        $nome = trim($_POST['nome'] ?? '');
        $doc = frutibankValidarCpfCnpj($_POST['cpf_cnpj'] ?? '');

        if ($nome === '') {
            frutibankJson(['ok' => false, 'msg' => 'Informe o nome do cliente.'], 400);
        }
        if ($doc === null) {
            frutibankJson(['ok' => false, 'msg' => 'CPF ou CNPJ inválido. Confira os dígitos.'], 400);
        }

        $stmt = $mysqli->prepare('SELECT id FROM frutibank_clientes WHERE user_id = ? AND cpf_cnpj = ? LIMIT 1');
        $stmt->bind_param('is', $user_id, $doc);
        $stmt->execute();
        $existe = $stmt->get_result()->fetch_row();
        $stmt->close();
        if ($existe) {
            frutibankJson(['ok' => false, 'msg' => 'Já existe um cliente com este CPF/CNPJ.'], 400);
        }

        $stmt = $mysqli->prepare('INSERT INTO frutibank_clientes (user_id, nome, cpf_cnpj) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $user_id, $nome, $doc);
        $stmt->execute();
        $novoId = (int)$stmt->insert_id;
        $stmt->close();
        frutibankJson(['ok' => true, 'msg' => 'Cliente cadastrado.', 'id' => $novoId]);

    case 'excluir_cliente':
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $stmt = $mysqli->prepare('SELECT COUNT(*) AS c FROM frutibank_cobrancas WHERE cliente_id = ? AND user_id = ?');
        $stmt->bind_param('ii', $clienteId, $user_id);
        $stmt->execute();
        $temCobranca = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0) > 0;
        $stmt->close();
        if ($temCobranca) {
            frutibankJson(['ok' => false, 'msg' => 'Este cliente possui cobranças geradas e não pode ser excluído.'], 400);
        }
        $stmt = $mysqli->prepare('DELETE FROM frutibank_clientes WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $clienteId, $user_id);
        $stmt->execute();
        $stmt->close();
        frutibankJson(['ok' => true, 'msg' => 'Cliente excluído.']);

    /* ---------------- Cobranças ---------------- */

    case 'listar_cobrancas':
        $sql = "SELECT fc.id, fc.valor, fc.descricao, fc.vencimento, fc.status, fc.criado_em,
                       c.nome AS cliente_nome, c.cpf_cnpj AS cliente_doc
                FROM frutibank_cobrancas fc
                JOIN frutibank_clientes c ON c.id = fc.cliente_id
                WHERE fc.user_id = $user_id
                ORDER BY fc.criado_em DESC
                LIMIT 300";
        $rows = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);
        frutibankJson(['ok' => true, 'cobrancas' => $rows]);

    case 'criar_cobranca':
        $config = frutibankGetConfig($mysqli, $user_id);
        if (!$config) {
            frutibankJson(['ok' => false, 'msg' => 'Cadastre sua chave PIX antes de gerar cobranças.'], 400);
        }

        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $valor = frutibankParseValor((string)($_POST['valor'] ?? '0'));
        $descricao = trim($_POST['descricao'] ?? '');
        $vencimento = trim($_POST['vencimento'] ?? '');

        if ($valor <= 0 || $valor > 9999999.99) {
            frutibankJson(['ok' => false, 'msg' => 'Informe um valor válido.'], 400);
        }

        $stmt = $mysqli->prepare('SELECT id FROM frutibank_clientes WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->bind_param('ii', $clienteId, $user_id);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_row();
        $stmt->close();
        if (!$cliente) {
            frutibankJson(['ok' => false, 'msg' => 'Cliente não encontrado.'], 404);
        }

        $vencDb = null;
        if ($vencimento !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $vencimento);
            if (!$dt) {
                frutibankJson(['ok' => false, 'msg' => 'Data de vencimento inválida.'], 400);
            }
            $vencDb = $dt->format('Y-m-d');
        }

        // txid único e curto (A-Z0-9, máx. 25) — identifica a cobrança no extrato
        $txid = 'FRTB' . strtoupper(base_convert((string)time(), 10, 36)) . strtoupper(bin2hex(random_bytes(4)));
        $txid = substr($txid, 0, 25);

        $payload = frutibankPixPayload(
            (string)$config['chave_pix'],
            (string)$config['nome_recebedor'],
            (string)$config['cidade'],
            $valor,
            $txid,
            $descricao !== '' ? $descricao : null
        );

        $descDb = $descricao !== '' ? mb_substr($descricao, 0, 140) : null;
        $stmt = $mysqli->prepare("
            INSERT INTO frutibank_cobrancas (user_id, cliente_id, valor, descricao, vencimento, txid, payload)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iidssss', $user_id, $clienteId, $valor, $descDb, $vencDb, $txid, $payload);
        $stmt->execute();
        $novoId = (int)$stmt->insert_id;
        $stmt->close();

        frutibankJson(['ok' => true, 'msg' => 'Cobrança gerada.', 'id' => $novoId, 'url' => '/home/frutibank_cobranca?id=' . $novoId]);

    case 'atualizar_status':
        $cobrancaId = (int)($_POST['cobranca_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['pendente', 'pago', 'cancelada'], true)) {
            frutibankJson(['ok' => false, 'msg' => 'Status inválido.'], 400);
        }
        $stmt = $mysqli->prepare('UPDATE frutibank_cobrancas SET status = ? WHERE id = ? AND user_id = ?');
        $stmt->bind_param('sii', $status, $cobrancaId, $user_id);
        $stmt->execute();
        $stmt->close();
        frutibankJson(['ok' => true, 'msg' => 'Status atualizado.']);

    default:
        frutibankJson(['ok' => false, 'msg' => 'Ação inválida.'], 400);
}
