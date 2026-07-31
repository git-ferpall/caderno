<?php
declare(strict_types=1);

require_once __DIR__ . '/../conta/helpers.php';

/**
 * Monta o resumo de tarefas da semana por propriedade.
 *
 * @return list<array{nome_razao:string,atrasadas:int,pendentes:int}>
 */
function alertaResumoPropriedades(mysqli $mysqli, int $contaId, ?int $funcionarioId = null): array
{
    $hoje = new DateTime('today');
    $domingo = (clone $hoje)->modify('sunday this week');

    $filtroProp = '';
    if ($funcionarioId) {
        $filtroProp = contaFuncionarioSqlFiltroPropriedades($mysqli, $funcionarioId, 'id');
    }

    $stmt = $mysqli->prepare("
        SELECT id, nome_razao
        FROM propriedades
        WHERE user_id = ?{$filtroProp}
        ORDER BY nome_razao
    ");
    $stmt->bind_param('i', $contaId);
    $stmt->execute();
    $propriedades = $stmt->get_result();
    $stmt->close();

    $resumo = [];

    while ($p = $propriedades->fetch_assoc()) {
        $stmtApt = $mysqli->prepare("
            SELECT data
            FROM apontamentos
            WHERE propriedade_id = ?
              AND status = 'pendente'
        ");
        $propId = (int)$p['id'];
        $stmtApt->bind_param('i', $propId);
        $stmtApt->execute();
        $apontamentos = $stmtApt->get_result();
        $stmtApt->close();

        $atrasadas = 0;
        $pendentes = 0;

        while ($a = $apontamentos->fetch_assoc()) {
            $data = new DateTime($a['data']);
            if ($data < $hoje) {
                $atrasadas++;
            } elseif ($data <= $domingo) {
                $pendentes++;
            }
        }

        if ($atrasadas === 0 && $pendentes === 0) {
            continue;
        }

        $resumo[] = [
            'nome_razao' => (string)$p['nome_razao'],
            'atrasadas'  => $atrasadas,
            'pendentes'  => $pendentes,
        ];
    }

    return $resumo;
}

/**
 * Destinatários de SMS semanal: dono da conta + funcionários (admin e apontador).
 *
 * @return list<array{conta_id:int,funcionario_id:?int,nome:string,telefone:string}>
 */
function alertaDestinatariosSms(mysqli $mysqli): array
{
    require_once __DIR__ . '/../../configuracao/usuarios_local.php';
    usuariosEnsureSchema($mysqli);

    $dest = [];

    $owners = $mysqli->query("
        SELECT user_id, nome, telefone
        FROM contato_cliente
        WHERE aceita_sms = 1
          AND telefone IS NOT NULL
          AND telefone != ''
    ");
    while ($row = $owners->fetch_assoc()) {
        $dest[] = [
            'conta_id'       => (int)$row['user_id'],
            'funcionario_id' => null,
            'nome'           => (string)$row['nome'],
            'telefone'       => (string)$row['telefone'],
        ];
    }

    $funcs = $mysqli->query("
        SELECT id, conta_pai, nome, telefone
        FROM usuarios_caderno
        WHERE conta_pai IS NOT NULL
          AND ativo = 1
          AND aceita_sms = 1
          AND telefone IS NOT NULL
          AND telefone != ''
    ");
    while ($row = $funcs->fetch_assoc()) {
        $dest[] = [
            'conta_id'       => (int)$row['conta_pai'],
            'funcionario_id' => (int)$row['id'],
            'nome'           => (string)$row['nome'],
            'telefone'       => (string)$row['telefone'],
        ];
    }

    return $dest;
}

/**
 * Destinatários de e-mail semanal: dono da conta + funcionários.
 *
 * @return list<array{conta_id:int,funcionario_id:?int,nome:string,email:string}>
 */
function alertaDestinatariosEmail(mysqli $mysqli): array
{
    require_once __DIR__ . '/../../configuracao/usuarios_local.php';
    usuariosEnsureSchema($mysqli);

    $dest = [];

    $owners = $mysqli->query("
        SELECT user_id, nome, email
        FROM contato_cliente
        WHERE aceita_email = 1
          AND email IS NOT NULL
          AND email != ''
    ");
    while ($row = $owners->fetch_assoc()) {
        $dest[] = [
            'conta_id'       => (int)$row['user_id'],
            'funcionario_id' => null,
            'nome'           => (string)$row['nome'],
            'email'          => (string)$row['email'],
        ];
    }

    $funcs = $mysqli->query("
        SELECT id, conta_pai, nome, email
        FROM usuarios_caderno
        WHERE conta_pai IS NOT NULL
          AND ativo = 1
          AND aceita_email = 1
          AND email IS NOT NULL
          AND email != ''
    ");
    while ($row = $funcs->fetch_assoc()) {
        $dest[] = [
            'conta_id'       => (int)$row['conta_pai'],
            'funcionario_id' => (int)$row['id'],
            'nome'           => (string)$row['nome'],
            'email'          => (string)$row['email'],
        ];
    }

    return $dest;
}
