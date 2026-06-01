<?php
/**
 * Validações centralizadas do Silo de Dados (upload, nomes, caminhos).
 */

const SILO_MAX_BYTES = 25 * 1024 * 1024; // 25 MB por arquivo

const SILO_MIMES_PERMITIDOS = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'application/pdf' => 'pdf',
    'text/plain' => 'txt',
];

const SILO_EXTENSOES_BLOQUEADAS = [
    'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phar',
    'exe', 'sh', 'bash', 'bat', 'cmd', 'com', 'msi',
    'js', 'mjs', 'html', 'htm', 'xhtml', 'svg', 'xml',
    'htaccess', 'cgi', 'pl', 'py', 'rb', 'asp', 'aspx', 'jsp',
];

function siloSanitizarNomeExibicao(string $nome): string
{
    $nome = trim($nome);
    $nome = str_replace(["\0", "\r", "\n"], '', $nome);
    $nome = basename($nome);
    if ($nome === '' || preg_match('/[\/\\\\:*?"<>|]/', $nome)) {
        throw new InvalidArgumentException('nome_invalido');
    }
    return $nome;
}

function siloValidarExtensaoNome(string $nome): void
{
    $partes = explode('.', strtolower($nome));
    array_shift($partes);
    foreach ($partes as $ext) {
        if ($ext === '' || in_array($ext, SILO_EXTENSOES_BLOQUEADAS, true)) {
            throw new InvalidArgumentException('extensao_proibida');
        }
    }
}

function siloDetectarMime(string $tmpPath): string
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        throw new RuntimeException('finfo_indisponivel');
    }
    $mime = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);
    return strtolower((string)$mime);
}

function siloValidarMagicBytes(string $tmpPath, string $mime): void
{
    $head = file_get_contents($tmpPath, false, null, 0, 512);
    if ($head === false) {
        throw new InvalidArgumentException('arquivo_ilegivel');
    }

    if (preg_match('/<\?(php|=)|<\?|base64_decode\s*\(|eval\s*\(|shell_exec\s*\(|system\s*\(|passthru\s*\(/i', $head)) {
        throw new InvalidArgumentException('arquivo_malicioso');
    }

    $ok = match ($mime) {
        'image/jpeg' => str_starts_with($head, "\xFF\xD8\xFF"),
        'image/png'  => str_starts_with($head, "\x89PNG\r\n\x1a\n"),
        'application/pdf' => str_starts_with($head, '%PDF'),
        'text/plain' => !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $head),
        default => false,
    };

    if (!$ok) {
        throw new InvalidArgumentException('conteudo_invalido');
    }
}

function siloGerarNomeArmazenamento(string $mime): string
{
    if (!isset(SILO_MIMES_PERMITIDOS[$mime])) {
        throw new InvalidArgumentException('mime_invalido');
    }
    return bin2hex(random_bytes(16)) . '.' . SILO_MIMES_PERMITIDOS[$mime];
}

function siloValidarArquivoUpload(array $arquivo): array
{
    if (empty($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
        throw new InvalidArgumentException('nenhum_arquivo');
    }

    if (!empty($arquivo['error']) && $arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('upload_falhou');
    }

    if ($arquivo['size'] > SILO_MAX_BYTES) {
        throw new InvalidArgumentException('arquivo_grande');
    }

    $nomeOriginal = siloSanitizarNomeExibicao($arquivo['name'] ?? 'arquivo');
    siloValidarExtensaoNome($nomeOriginal);

    $mime = siloDetectarMime($arquivo['tmp_name']);
    if (!isset(SILO_MIMES_PERMITIDOS[$mime])) {
        throw new InvalidArgumentException('tipo_nao_permitido');
    }

    siloValidarMagicBytes($arquivo['tmp_name'], $mime);

    return [
        'nome_original' => $nomeOriginal,
        'mime' => $mime,
        'nome_armazenamento' => siloGerarNomeArmazenamento($mime),
        'tamanho' => (int)$arquivo['size'],
    ];
}

function siloCaminhoDentroDeBase(string $base, string $path): bool
{
    $baseReal = realpath($base);
    $pathReal = realpath($path);
    if ($baseReal === false || $pathReal === false) {
        return false;
    }
    $baseNorm = rtrim(str_replace('\\', '/', $baseReal), '/');
    $pathNorm = str_replace('\\', '/', $pathReal);
    return $pathNorm === $baseNorm || str_starts_with($pathNorm, $baseNorm . '/');
}

function siloVerificarQuota($mysqli, int $user_id, int $bytesNovos): void
{
    $stmt = $mysqli->prepare('SELECT limite_mb FROM silo_limites WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $limite_mb = isset($row['limite_mb']) ? (int)$row['limite_mb'] : 1024;
    $limite_bytes = $limite_mb * 1024 * 1024;

    $stmt = $mysqli->prepare('SELECT COALESCE(SUM(tamanho_bytes), 0) AS total FROM silo_arquivos WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $uso = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    if ($uso + $bytesNovos > $limite_bytes) {
        throw new InvalidArgumentException('limite_armazenamento');
    }
}
