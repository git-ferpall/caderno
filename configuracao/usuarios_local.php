<?php
declare(strict_types=1);

/**
 * Usuários do Caderno (tabela usuarios_caderno)
 * ----------------------------------------------
 * Centraliza usuários locais (com senha) e usuários Frutag
 * (auto-provisionados no login), além dos perfis de acesso:
 * 'usuario' | 'representante' | 'admin'.
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/configuracao_conexao.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

const USUARIO_PERFIS = ['usuario', 'representante', 'admin'];

// Papéis de funcionários dentro de uma conta (usuarios com conta_pai preenchido):
//   'admin'     → gerencia todo o caderno da conta, inclusive outros funcionários
//   'apontador' → só registra apontamentos (sem gestão de cadastros/relatórios)
const CONTA_PAPEIS = ['admin', 'apontador'];

function usuariosEnsureSchema(mysqli $mysqli): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    $mysqli->query("
        CREATE TABLE IF NOT EXISTS usuarios_caderno (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            origem ENUM('frutag','local') NOT NULL DEFAULT 'local',
            tipo_frutag VARCHAR(20) NULL,
            login VARCHAR(100) NULL,
            email VARCHAR(255) NULL,
            senha_hash VARCHAR(255) NULL,
            nome VARCHAR(255) NULL,
            perfil ENUM('usuario','representante','admin') NOT NULL DEFAULT 'usuario',
            criado_por INT UNSIGNED NULL,
            conta_pai INT UNSIGNED NULL,
            papel_conta ENUM('admin','apontador') NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_usuarios_caderno_login (login),
            KEY idx_usuarios_caderno_criado_por (criado_por),
            KEY idx_usuarios_caderno_conta_pai (conta_pai),
            KEY idx_usuarios_caderno_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1000000
    ");

    // Migração in-place para bancos criados antes dos funcionários de conta
    $col = $mysqli->query("SHOW COLUMNS FROM usuarios_caderno LIKE 'conta_pai'");
    if ($col && $col->num_rows === 0) {
        $mysqli->query("
            ALTER TABLE usuarios_caderno
                ADD COLUMN conta_pai INT UNSIGNED NULL AFTER criado_por,
                ADD COLUMN papel_conta ENUM('admin','apontador') NULL AFTER conta_pai,
                ADD KEY idx_usuarios_caderno_conta_pai (conta_pai)
        ");
    }

    // Bootstrap: se ainda não existe nenhum admin, promove os IDs de CADERNO_BOOTSTRAP_ADMINS
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM usuarios_caderno WHERE perfil = 'admin'");
    $count = (int)($res->fetch_assoc()['c'] ?? 0);
    if ($count === 0 && defined('CADERNO_BOOTSTRAP_ADMINS') && CADERNO_BOOTSTRAP_ADMINS !== '') {
        $ids = array_filter(array_map('intval', explode(',', CADERNO_BOOTSTRAP_ADMINS)));
        $stmt = $mysqli->prepare("
            INSERT INTO usuarios_caderno (id, origem, tipo_frutag, nome, perfil)
            VALUES (?, 'frutag', 'cliente', 'Admin bootstrap', 'admin')
            ON DUPLICATE KEY UPDATE perfil = 'admin'
        ");
        foreach ($ids as $id) {
            if ($id > 0) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
        }
        $stmt->close();
    }
}

function usuarioBuscarPorId(mysqli $mysqli, int $id): ?array
{
    usuariosEnsureSchema($mysqli);
    $stmt = $mysqli->prepare("SELECT * FROM usuarios_caderno WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Busca usuário LOCAL por login ou e-mail (para autenticação com senha). */
function usuarioBuscarLocalPorLogin(mysqli $mysqli, string $login): ?array
{
    usuariosEnsureSchema($mysqli);
    $stmt = $mysqli->prepare("
        SELECT * FROM usuarios_caderno
        WHERE origem = 'local' AND (login = ? OR email = ?)
        LIMIT 1
    ");
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Perfil efetivo (relido do banco). Retorna null se não existir ou estiver inativo. */
function usuarioPerfil(mysqli $mysqli, int $id): ?string
{
    $u = usuarioBuscarPorId($mysqli, $id);
    if (!$u || (int)$u['ativo'] !== 1) return null;
    return $u['perfil'];
}

/**
 * Verifica se um login ou e-mail está disponível como credencial de um novo
 * usuário local (não colide com logins existentes nem com e-mails de locais).
 */
function usuarioCredencialDisponivel(mysqli $mysqli, string $valor): bool
{
    $valor = strtolower(trim($valor));
    if ($valor === '') return false;
    usuariosEnsureSchema($mysqli);

    $stmt = $mysqli->prepare("
        SELECT id FROM usuarios_caderno
        WHERE login = ?
           OR (origem = 'local' AND email IS NOT NULL AND email = ?)
        LIMIT 1
    ");
    $stmt->bind_param('ss', $valor, $valor);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_row();
    $stmt->close();
    return !$existe;
}

/**
 * Cria usuário local. $dados: nome, login, senha, email (opcional), perfil (opcional),
 * conta_pai + papel_conta (opcionais — funcionário vinculado a uma conta principal).
 * Lança InvalidArgumentException com mensagem amigável em caso de validação.
 */
function usuarioCriarLocal(mysqli $mysqli, array $dados, ?int $criado_por = null): int
{
    usuariosEnsureSchema($mysqli);

    $nome   = trim((string)($dados['nome'] ?? ''));
    $login  = strtolower(trim((string)($dados['login'] ?? '')));
    $email  = strtolower(trim((string)($dados['email'] ?? '')));
    $senha  = (string)($dados['senha'] ?? '');
    $perfil = $dados['perfil'] ?? 'usuario';

    $contaPai   = (int)($dados['conta_pai'] ?? 0);
    $papelConta = (string)($dados['papel_conta'] ?? '');
    if ($contaPai > 0) {
        $perfil = 'usuario'; // funcionário nunca tem perfil de plataforma
        if (!in_array($papelConta, CONTA_PAPEIS, true)) {
            throw new InvalidArgumentException('Papel do funcionário inválido.');
        }
    }

    if ($nome === '') {
        throw new InvalidArgumentException('Informe o nome do usuário.');
    }
    if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,99}$/', $login)) {
        throw new InvalidArgumentException('Login inválido: use ao menos 3 caracteres (letras, números, ponto, hífen ou underline).');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('E-mail inválido.');
    }
    if (strlen($senha) < 8) {
        throw new InvalidArgumentException('A senha deve ter pelo menos 8 caracteres.');
    }
    if (!in_array($perfil, USUARIO_PERFIS, true)) {
        throw new InvalidArgumentException('Perfil inválido.');
    }

    // login/e-mail não podem colidir com outro usuário local (login e e-mail
    // servem de credencial no login, então precisam ser únicos entre si)
    if (!usuarioCredencialDisponivel($mysqli, $login)
        || ($email !== '' && !usuarioCredencialDisponivel($mysqli, $email))) {
        throw new InvalidArgumentException('Já existe um usuário com este login ou e-mail.');
    }

    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $emailDb = $email !== '' ? $email : null;

    if ($contaPai > 0) {
        $stmt = $mysqli->prepare("
            INSERT INTO usuarios_caderno (origem, login, email, senha_hash, nome, perfil, criado_por, conta_pai, papel_conta)
            VALUES ('local', ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sssssiis', $login, $emailDb, $hash, $nome, $perfil, $criado_por, $contaPai, $papelConta);
    } else {
        $stmt = $mysqli->prepare("
            INSERT INTO usuarios_caderno (origem, login, email, senha_hash, nome, perfil, criado_por)
            VALUES ('local', ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sssssi', $login, $emailDb, $hash, $nome, $perfil, $criado_por);
    }
    $stmt->execute();
    $novoId = (int)$stmt->insert_id;
    $stmt->close();
    return $novoId;
}

/**
 * Valida a sessão de um funcionário de conta: ele precisa existir, estar ativo,
 * pertencer à conta informada e a conta principal precisa estar ativa.
 * Retorna o registro do funcionário (com papel_conta atual) ou null se inválido.
 */
function usuarioValidarFuncionario(mysqli $mysqli, int $funcId, int $contaId): ?array
{
    if ($funcId <= 0 || $contaId <= 0) return null;
    usuariosEnsureSchema($mysqli);

    $stmt = $mysqli->prepare("
        SELECT f.*, p.ativo AS conta_ativa
        FROM usuarios_caderno f
        LEFT JOIN usuarios_caderno p ON p.id = f.conta_pai
        WHERE f.id = ? AND f.conta_pai = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $funcId, $contaId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;
    if ((int)$row['ativo'] !== 1) return null;
    if ($row['conta_ativa'] !== null && (int)$row['conta_ativa'] !== 1) return null;
    return $row;
}

/**
 * Liberação de funcionários por conta (aprovada pelo administrativo).
 * A conta só pode criar acessos de funcionários se tiver um registro em
 * conta_funcionarios_config, que também define o limite de acessos ativos.
 */
function contaFuncEnsureSchema(mysqli $mysqli): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    $mysqli->query("
        CREATE TABLE IF NOT EXISTS conta_funcionarios_config (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            limite INT UNSIGNED NOT NULL DEFAULT 1,
            habilitado_por INT UNSIGNED NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

/** Config de liberação da conta (['limite' => N, ...]) ou null se não liberada. */
function contaFuncConfig(mysqli $mysqli, int $contaId): ?array
{
    if ($contaId <= 0) return null;
    contaFuncEnsureSchema($mysqli);
    $stmt = $mysqli->prepare("SELECT * FROM conta_funcionarios_config WHERE user_id = ? LIMIT 1");
    $stmt->bind_param('i', $contaId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Quantidade de funcionários ATIVOS da conta (conta para o limite). */
function contaFuncContarAtivos(mysqli $mysqli, int $contaId): int
{
    usuariosEnsureSchema($mysqli);
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS c FROM usuarios_caderno WHERE conta_pai = ? AND ativo = 1");
    $stmt->bind_param('i', $contaId);
    $stmt->execute();
    $c = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();
    return $c;
}

/** Quantidade total de funcionários da conta (ativos ou não). */
function contaFuncContarTotal(mysqli $mysqli, int $contaId): int
{
    usuariosEnsureSchema($mysqli);
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS c FROM usuarios_caderno WHERE conta_pai = ?");
    $stmt->bind_param('i', $contaId);
    $stmt->execute();
    $c = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();
    return $c;
}

/**
 * Garante que um usuário Frutag exista em usuarios_caderno (auto-provisionamento).
 * Nunca rebaixa perfil nem altera 'ativo' de um registro existente.
 */
function usuarioUpsertFrutag(mysqli $mysqli, int $id, ?string $tipo = null, ?string $nome = null, ?string $email = null): void
{
    if ($id <= 0) return;
    usuariosEnsureSchema($mysqli);

    $tipo  = $tipo ?: 'cliente';
    $nome  = $nome !== null ? trim($nome) : null;
    $email = $email !== null ? strtolower(trim($email)) : null;

    $stmt = $mysqli->prepare("
        INSERT INTO usuarios_caderno (id, origem, tipo_frutag, nome, email, perfil)
        VALUES (?, 'frutag', ?, ?, ?, 'usuario')
        ON DUPLICATE KEY UPDATE
            tipo_frutag = VALUES(tipo_frutag),
            nome  = COALESCE(VALUES(nome), nome),
            email = COALESCE(VALUES(email), email)
    ");
    $stmt->bind_param('isss', $id, $tipo, $nome, $email);
    $stmt->execute();
    $stmt->close();
}

/**
 * Garante provisionamento de um usuário Frutag conhecido apenas pelas tabelas
 * legadas (propriedades / contato_cliente). Retorna o registro ou null.
 */
function usuarioGarantirFrutag(mysqli $mysqli, int $id): ?array
{
    $u = usuarioBuscarPorId($mysqli, $id);
    if ($u) return $u;

    $stmt = $mysqli->prepare("
        SELECT COALESCE(
                   (SELECT nome  FROM contato_cliente WHERE user_id = ? LIMIT 1),
                   (SELECT nome_razao FROM propriedades WHERE user_id = ? ORDER BY ativo DESC LIMIT 1)
               ) AS nome,
               COALESCE(
                   (SELECT email FROM contato_cliente WHERE user_id = ? LIMIT 1),
                   (SELECT email FROM propriedades WHERE user_id = ? ORDER BY ativo DESC LIMIT 1)
               ) AS email
    ");
    $stmt->bind_param('iiii', $id, $id, $id, $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || ($row['nome'] === null && $row['email'] === null)) {
        return null; // usuário desconhecido
    }
    usuarioUpsertFrutag($mysqli, $id, 'cliente', $row['nome'], $row['email']);
    return usuarioBuscarPorId($mysqli, $id);
}

/**
 * Emite um JWT do Caderno (HS256, mesmo segredo do SSO), 1h de validade.
 * $claims sobrescreve/complementa os padrões (sub, tipo, name, ...).
 */
function usuarioEmitirJwt(array $claims): string
{
    $now = time();
    $payload = array_merge([
        'iss' => 'https://frutag.com.br',
        'aud' => 'frutag-apps',
        'iat' => $now,
        'exp' => $now + 3600,
    ], $claims);
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

/** Opções de cookie compatíveis com o login (domínio .frutag.com.br em produção). */
function usuarioCookieOptions(int $ttl = 3600): array
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $opts = [
        'expires'  => $ttl > 0 ? time() + $ttl : time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isHttps,
    ];
    // fora do domínio frutag.com.br (ex.: dev local) o cookie de domínio seria rejeitado
    if (preg_match('/(^|\.)frutag\.com\.br(:\d+)?$/i', $host)) {
        $opts['domain'] = '.frutag.com.br';
    }
    return $opts;
}
