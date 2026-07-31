<?php
declare(strict_types=1);

require_once __DIR__ . '/https.php';

// inicia buffer de saída para evitar "headers already sent"
ob_start();

// carrega o middleware de autenticação (JWT)
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

// força login → se não estiver autenticado, redireciona para index.php
$user = require_login();
csrf_ensure_cookie();
if (!csrf_is_exempt_request()) {
    csrf_verify();
}

// sessão de funcionário de conta: revalida no banco (desativado = deslogado)
$conta_funcionario = caderno_conta_funcionario();

// apontador: bloqueia páginas de gestão (lista central em conta_guard.php)
if ($conta_funcionario !== null && caderno_conta_papel() === 'apontador') {
    require_once __DIR__ . '/conta_guard.php';
    if (conta_script_bloqueado(CONTA_APONTADOR_PAGINAS_BLOQUEADAS)) {
        require_conta_gestao(); // renderiza o 403 padrão
    }
}

// funcionário: garante que a propriedade ativa é uma das permitidas
if ($conta_funcionario !== null) {
    require_once __DIR__ . '/../funcoes/conta/helpers.php';
    contaFuncionarioGarantirPropriedadeAtiva(caderno_db(), (int)$user->sub, (int)$conta_funcionario['id']);
}

// $user agora contém as claims do JWT
// Ex: $user->sub, $user->name, $user->email

// deixa acessível globalmente
$GLOBALS['auth_user'] = $user;
$GLOBALS['conta_funcionario'] = $conta_funcionario;
