<?php
ob_start();
file_put_contents('/tmp/teste_login_caderno.log', "POST: " . print_r($_POST, true), FILE_APPEND);
file_put_contents('/tmp/teste_login_caderno.log', date("Y-m-d H:i:s") . " - ACESSOU O PROCESSA DO CADERNO\n", FILE_APPEND);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/configuracao_funcoes.php';

sec_session_start();

$login    = filter_input(INPUT_POST, 'email');
$password = filter_input(INPUT_POST, 'p');

$resposta_recaptcha = true;

if ($resposta_recaptcha && !empty($login) && !empty($password)) {

    $stmt = $mysqli->prepare("SELECT cli_ativo FROM cliente WHERE cli_login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $stmt->bind_result($cli_ativo);
    $stmt->fetch();
    $stmt->close();

    file_put_contents('/tmp/teste_login_caderno.log', "cli_ativo: $cli_ativo\n", FILE_APPEND);

    if ($cli_ativo === "N") {
        file_put_contents('/tmp/teste_login_caderno.log', "Conta inativa: $login\n", FILE_APPEND);
        $_SESSION['retorno'] = array(
            'tipo' => 'erro',
            'mensagem' => 'Conta inativa. Entre em contato com o suporte.',
            'tempo' => 4000
        );
        header("Location: ../index.php");
        exit();
    }

    if (login($login, $password, $mysqli) === true) {
        file_put_contents('/tmp/login_debug.log', "login() retornou TRUE\n", FILE_APPEND);

        // Verifica domínio e direciona
        if ($_SESSION['perfil_adicional']['status'] == 0) {
            if ($_SERVER['HTTP_HOST'] === 'caderno.frutag.app.br') {
                file_put_contents('/tmp/teste_login_caderno.log', "Redirecionando para home/home.php\n", FILE_APPEND);
                header("Location: ../home/home.php");
            } else {
                file_put_contents('/tmp/teste_login_caderno.log', "Redirecionando para etiquetas.php\n", FILE_APPEND);
                header("Location: ../etiquetas.php");
            }
            exit();
        }

        if ($_SESSION['perfil_adicional']['etiquetas'] == 1) {
            file_put_contents('/tmp/teste_login_caderno.log', "Redirecionando para etiquetas.php\n", FILE_APPEND);
            header("Location: ../etiquetas.php");
            exit();
        } elseif ($_SESSION['perfil_adicional']['produtos'] == 1) {
            file_put_contents('/tmp/teste_login_caderno.log', "Redirecionando para produto_lista.php\n", FILE_APPEND);
            header("Location: ../produto_lista.php");
            exit();
        } elseif ($_SESSION['perfil_adicional']['fornecedores'] == 1) {
            file_put_contents('/tmp/teste_login_caderno.log', "Redirecionando para fornecedor_lista.php\n", FILE_APPEND);
            header("Location: ../fornecedor_lista.php");
            exit();
        } elseif ($_SESSION['perfil_adicional']['varejistas'] == 1) {
            file_put_contents('/tmp/teste_login_caderno.log', "Redirecionando para varejista_lista.php\n", FILE_APPEND);
            header("Location: ../varejista_lista.php");
            exit();
        } elseif ($_SESSION['perfil_adicional']['estoque'] == 1) {
            file_put_contents('/tmp/teste_login_caderno.log', "Redirecionando para estoque_lista.php\n", FILE_APPEND);
            header("Location: ../estoque_lista.php");
            exit();
        }

        // Caso não tenha permissão
        file_put_contents('/tmp/teste_login_caderno.log', "Sem permissões atribuídas: $login\n", FILE_APPEND);
        $_SESSION['retorno'] = array(
            'tipo' => 'erro',
            'mensagem' => 'Sem permissões atribuídas. Contate o suporte.',
            'tempo' => 4000
        );
        header("Location: ../index.php");
        exit();

    } else {
        file_put_contents('/tmp/login_debug.log', "login() retornou FALSE\n", FILE_APPEND);
        $_SESSION['retorno'] = array(
            'tipo' => 'erro',
            'mensagem' => 'Usuário ou senha inválidos.',
            'tempo' => 3000
        );
        header("Location: ../index.php");
        exit();
    }

} else {
    file_put_contents('/tmp/teste_login_caderno.log', "Campos obrigatórios ausentes ou captcha\n", FILE_APPEND);
    $_SESSION['retorno'] = array(
        'tipo' => 'erro',
        'mensagem' => 'Preencha todos os campos.',
        'tempo' => 4000
    );
    header("Location: ../index.php");
    exit();
}

ob_end_flush();
