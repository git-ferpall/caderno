<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/https.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/recaptcha.php'; // 🔒 chaves do Google
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/login_rate_limit.php';

session_start();

/**
 * ==================================================
 * Função auxiliar: salva mensagem de erro na sessão
 * ==================================================
 */
function setLoginError($mensagem) {
    $_SESSION['retorno'] = [
        'mensagem' => $mensagem,
        'hora' => date('H:i:s')
    ];
}

$login   = trim($_POST['login'] ?? '');
$senha   = trim($_POST['senha'] ?? '');
$next    = $_POST['next'] ?? '/';
$captcha = trim($_POST['g-recaptcha-response'] ?? ''); // token reCAPTCHA

if ($login === '' || $senha === '') {
    setLoginError('Por favor, preencha usuário e senha.');
    header('Location: /');
    exit;
}

if (login_rate_limit_is_blocked($login)) {
    setLoginError(login_rate_limit_block_message($login));
    header('Location: /');
    exit;
}

/**
 * ==================================================
 * 1️⃣  Verifica se o token chegou
 * ==================================================
 */
if (empty($captcha)) {
    error_log("reCAPTCHA token vazio");
    login_rate_limit_record_failure($login);
    setLoginError('Validação de segurança falhou. Recarregue a página e tente novamente.');
    header('Location: /');
    exit;
}

/**
 * ==================================================
 * 2️⃣  Validação reCAPTCHA v3 (via cURL)
 * ==================================================
 */
$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';

$ch = curl_init($recaptcha_url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $captcha,
        'remoteip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if (!$response) {
    error_log("reCAPTCHA erro cURL: $error");
    setLoginError('Erro ao validar o reCAPTCHA. Tente novamente.');
    header('Location: /');
    exit;
}

$captcha_data = json_decode($response, true);

// se não houver sucesso ou score muito baixo, bloqueia
$score = $captcha_data['score'] ?? 0;
if (empty($captcha_data['success']) || $score < 0.2) {
    error_log("reCAPTCHA falhou: score=" . ($score ?: 'null'));
    login_rate_limit_record_failure($login);
    setLoginError('Falha na validação de segurança. Tente novamente.');
    header('Location: /');
    exit;
}

/**
 * ==================================================
 * 3️⃣  Usuário LOCAL: autentica no banco do Caderno
 * ==================================================
 * Se o login/e-mail pertencer a um usuário local, valida a senha
 * aqui mesmo (password_verify) e emite o JWT do Caderno.
 * Caso contrário, segue para a API Frutag (fluxo original).
 */
require_once __DIR__ . '/usuarios_local.php'; // também conecta no banco ($mysqli)

$usuarioLocal = usuarioBuscarLocalPorLogin($mysqli, $login);
if ($usuarioLocal) {
    if ((int)$usuarioLocal['ativo'] !== 1) {
        setLoginError('Usuário desativado. Contate o administrador.');
        header('Location: /');
        exit;
    }
    if (!password_verify($senha, (string)$usuarioLocal['senha_hash'])) {
        login_rate_limit_record_failure($login);
        setLoginError('Usuário ou senha incorretos.');
        header('Location: /');
        exit;
    }

    if (!empty($usuarioLocal['conta_pai'])) {
        // Funcionário de conta: o caderno acessado é o da conta principal.
        // O JWT sai com sub = conta principal (dona dos dados) e claims extras
        // identificando o funcionário e seu papel dentro da conta.
        $conta = usuarioBuscarPorId($mysqli, (int)$usuarioLocal['conta_pai']);
        if (!$conta || (int)$conta['ativo'] !== 1) {
            setLoginError('A conta principal deste acesso está desativada. Contate o responsável.');
            header('Location: /');
            exit;
        }
        $tipoConta = $conta['origem'] === 'local' ? 'local' : ($conta['tipo_frutag'] ?: 'cliente');
        $jwt = usuarioEmitirJwt([
            'sub'        => (int)$conta['id'],
            'tipo'       => $tipoConta,
            'name'       => $conta['nome'],
            'email'      => $conta['email'],
            'perfil'     => 'usuario', // funcionário nunca herda perfil de plataforma da conta
            'func_id'    => (int)$usuarioLocal['id'],
            'func_nome'  => $usuarioLocal['nome'],
            'func_papel' => $usuarioLocal['papel_conta'] ?: 'apontador',
        ]);
    } else {
        $jwt = usuarioEmitirJwt([
            'sub'    => (int)$usuarioLocal['id'],
            'tipo'   => 'local',
            'name'   => $usuarioLocal['nome'],
            'email'  => $usuarioLocal['email'],
            'perfil' => $usuarioLocal['perfil'],
        ]);
    }
    setcookie(AUTH_COOKIE, $jwt, usuarioCookieOptions(3600));
    csrf_issue_token(3600);
    login_rate_limit_reset($login);

    $destino = caderno_safe_redirect_path($next);
    header('Location: ' . $destino);
    exit;
}

/**
 * ==================================================
 * 3️⃣b  Monta o payload da API de autenticação (Frutag)
 * ==================================================
 */
$payload = [
    'login' => $login,
    'senha' => $senha,
    'g-recaptcha-response' => $captcha,
];

/**
 * ==================================================
 * 4️⃣  Chamada da API (via função http_post_form)
 * ==================================================
 */
$r = http_post_form(AUTH_API_LOGIN, $payload);

if (!$r || ($r['status'] ?? 0) === 0 || ($r['body'] ?? '') === '' || ($r['status'] ?? 0) >= 500) {
    error_log("AUTH_API erro rede/5xx status=" . ($r['status'] ?? 'null'));
    setLoginError('Erro de comunicação com o servidor. Tente novamente mais tarde.');
    header('Location: /');
    exit;
}

/**
 * ==================================================
 * 5️⃣  Trata erros de autenticação
 * ==================================================
 */
if (($r['status'] ?? 0) === 401) {
    error_log("AUTH_API 401 body=" . substr($r['body'], 0, 400));
    login_rate_limit_record_failure($login);
    setLoginError('Usuário ou senha incorretos.');
    header('Location: /');
    exit;
}

if (($r['status'] ?? 0) === 403) {
    error_log("AUTH_API 403 body=" . substr($r['body'], 0, 400));
    login_rate_limit_record_failure($login);
    setLoginError('Usuário sem permissão para acessar o Caderno de Campo.');
    header('Location: /');
    exit;
}

$j = json_decode($r['body'], true);
if (!is_array($j) || empty($j['ok']) || empty($j['token'])) {
    error_log("AUTH_API sem ok/token body=" . substr($r['body'], 0, 400));
    login_rate_limit_record_failure($login);
    setLoginError('Falha na autenticação. Verifique suas credenciais.');
    header('Location: /');
    exit;
}

/**
 * ==================================================
 * 5️⃣b  Auto-provisiona o usuário Frutag no Caderno
 * ==================================================
 * Registra (ou atualiza) o usuário em usuarios_caderno para que ele
 * apareça no painel administrativo e possa receber perfis.
 */
try {
    $tokenParts = explode('.', $j['token']);
    $tokenPayload = json_decode(base64_decode(strtr($tokenParts[1] ?? '', '-_', '+/')), true) ?: [];
    $frutagId = (int)($tokenPayload['sub'] ?? 0);
    if ($frutagId > 0) {
        usuarioUpsertFrutag(
            $mysqli,
            $frutagId,
            $tokenPayload['tipo'] ?? 'cliente',
            $tokenPayload['name'] ?? null,
            $tokenPayload['email'] ?? null
        );
    }
} catch (Throwable $e) {
    error_log('[caderno] upsert usuario frutag falhou: ' . $e->getMessage());
}

/**
 * ==================================================
 * 6️⃣  Define o cookie JWT (AUTH_COOKIE)
 * ==================================================
 */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443');
$cookieOptions = [
    'expires'  => time() + 3600,
    'path'     => '/',
    'domain'   => '.frutag.com.br',   // 🔥 ESSENCIAL
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $isHttps,
];
setcookie(AUTH_COOKIE, $j['token'], $cookieOptions);
csrf_issue_token(3600);
login_rate_limit_reset($login);

error_log("AUTH_COOKIE setado (secure=" . ($cookieOptions['secure'] ? '1' : '0') . ") host=" . ($_SERVER['HTTP_HOST'] ?? ''));

/**
 * ==================================================
 * 7️⃣  Redireciona para a home (evita loop / → /home/ via SW)
 * ==================================================
 */
$destino = caderno_safe_redirect_path($next);
header('Location: ' . $destino);
exit;
