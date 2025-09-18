<?php

//FUNÇÕES DIVERSAS
function login($login, $password, $mysqli) {

    $login_adicional    = $login;
    $password_adicional = $password;

    // utilizar declarações preparadas significa que a injeção de código SQL não será possível. 
    if ($stmt = $mysqli->prepare("SELECT cli_cod, cli_login, cli_senha, cli_salt, cli_empresa, cli_ativo, cli_tipo, cli_email, cli_array_fotos FROM cliente WHERE BINARY cli_login = ? LIMIT 1")) {

        $stmt->bind_param('s', $login); // Vincula "$email" ao parâmetro.
        $stmt->execute(); // Executa a query preparada.
        $stmt->store_result();
        $stmt->bind_result($cod, $login, $senha, $salt, $cli_empresa, $cli_ativo, $cli_tipo, $cli_email, $cli_array_fotos); // obtém variáveis do resultado.
        $stmt->fetch();

        if ($stmt->num_rows == 1) { 

                $password = hash('sha512', $password . $salt); 
                file_put_contents('/tmp/login_debug.log', "Login OK para $login\n", FILE_APPEND);
                file_put_contents('/tmp/login_debug.log', "Comparando...\nSenha banco: $senha\nSenha enviada(p): $password\nSalt: $salt\n", FILE_APPEND);

            
            if ($senha == $password) { 
                
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_browser = $_SERVER['HTTP_USER_AGENT'];

                $cod = preg_replace("/[^0-9]+/", "", $cod); // Proteção XSS conforme poderíamos imprimir este valor
                $_SESSION['cliente_cod'] = $cod;
                $_SESSION['cliente_login'] = $login;
                $_SESSION['cliente_tipo'] = $cli_tipo;
                $_SESSION['cliente_ativo'] = $cli_ativo;
                $_SESSION['cliente_empresa'] = $cli_empresa;
                $_SESSION["cliente_email"] = $cli_email;
                $_SESSION['perfil_adicional'] = array(
                    'status'       => false,
                    'etiquetas'    => false,
                    'produtos'     => false,
                    'fornecedores' => false,
                    'varejistas'   => false,
                    'estoque'      => false
                );
                $_SESSION["caminho_base"] = CAMINHO_BASE;

                $_SESSION["cliente_logo"] = '';
                $array_fotos = unserialize($cli_array_fotos);
                if(is_array($array_fotos) && array_key_exists("logo", $array_fotos)){
                    $_SESSION["cliente_logo"] = $array_fotos["logo"];
                }

                $_SESSION['login_string'] = hash('sha512', $password . $ip_address . $user_browser);
                $_SESSION['auth'] = true;
                setcookie('mm-login', $cod, time() + 60 * 60 * 24 * 30, '/');
                
                return true;

            } else {
                // Senha não está correta
                // Nós armazenamos esta tentativa na base de dados
                file_put_contents('/tmp/login_debug.log', "Senha inválida para $login\n", FILE_APPEND);
                $now = time();
                $mysqli->query("INSERT INTO login_attempts (user_id, time) VALUES ('$cod', '$now')");
                return false;
            }
                
        } else {

            //BUSCA USUÁRIOS ADICIONAIS
            $stmt = $mysqli->query("SELECT * FROM cliente_perfil_adicional WHERE cli_login = '".$login_adicional."' LIMIT 1");

            if ($stmt->num_rows == 1) { 
                
                $stmt = $stmt->fetch_array();
                                
                $password_adicional = hash('sha512', $password_adicional.$stmt['cli_salt']);
                $senha              = $stmt['cli_senha'];
                
                if ($senha == $password_adicional) {
                                        
                    //BUSCA USUÁRIO MASTER
                    $stmt2 = $mysqli->query("SELECT * FROM cliente WHERE cli_cod = '".$stmt['cli_cod']."' LIMIT 1")->fetch_array();

                    $ip_address = $_SERVER['REMOTE_ADDR']; // Pega o endereço IP do usuário. 
                    $user_browser = $_SERVER['HTTP_USER_AGENT'];

                    $cod = preg_replace("/[^0-9]+/", "", $stmt2['cli_cod']); // Proteção XSS conforme poderíamos imprimir este valor
                    $_SESSION['cliente_cod'] = $cod;
                    $_SESSION['cliente_login'] = $stmt2['cli_login'];
                    $_SESSION['cliente_tipo'] = $stmt2['cli_tipo'];
                    $_SESSION['cliente_ativo'] = $stmt2['cli_ativo'];
                    $_SESSION['cliente_empresa'] = $stmt2['cli_empresa'];
                    $_SESSION["cliente_email"] = $stmt2['cli_email'];
                    $_SESSION['perfil_adicional'] = array(
                        'status'       => true,
                        'etiquetas'    => $stmt['cli_per_etiquetas']    == 1 ? true : false,
                        'produtos'     => $stmt['cli_per_produtos']     == 1 ? true : false,
                        'fornecedores' => $stmt['cli_per_fornecedores'] == 1 ? true : false,
                        'varejistas'   => $stmt['cli_per_varejistas']   == 1 ? true : false,
                        'estoque'      => $stmt['cli_per_estoque']      == 1 ? true : false
                    );
                    $_SESSION["caminho_base"] = CAMINHO_BASE;                    

                    $_SESSION["cliente_logo"] = '';
                    $array_fotos = unserialize($stmt2['cli_array_fotos']);
                    if(is_array($array_fotos) && array_key_exists("logo", $array_fotos)){
                        $_SESSION["cliente_logo"] = $array_fotos["logo"];
                    }

                    $_SESSION['login_string'] = hash('sha512', $password . $ip_address . $user_browser);
                    $_SESSION['auth'] = true;
                    setcookie('mm-login', $cod, time() + 60 * 60 * 24 * 30, '/');
                    
                    return true;

                } else {                    
                    return false;
                }
                
            } else {

                // Nenhum usuário existe. 
                return false;

            }

        }

    }

}

function checkbrute($user_id, $mysqli) {
    // Retorna a data atual
    $now = time();
    // Todas as tentativas de login são contadas pelas 2 últimas horas. 
    $valid_attempts = $now - (2 * 60 * 60);

    if ($stmt = $mysqli->prepare("SELECT time FROM login_attempts WHERE user_id = ? AND time > '$valid_attempts'")) {
        $stmt->bind_param('i', $user_id);
        // Executa a query preparada.
        $stmt->execute();
        $stmt->store_result();
        // Se houver mais de 5 tentativas falhas de login
        if ($stmt->num_rows > 5) {
            return true;
        } else {
            return false;
        }
    }
}

function login_check($mysqli) {
    // Verifica se todas as variáveis das sessões foram definidas
    if (isset($_SESSION['cliente_cod'], $_SESSION['login_string'])) {
        $cod = $_SESSION['cliente_cod'];
        $login_string = $_SESSION['login_string'];
        $ip_address = $_SERVER['REMOTE_ADDR']; // Pega o endereço IP do usuário 
        $user_browser = $_SERVER['HTTP_USER_AGENT']; // Pega a string do usuário.

        if ($stmt = $mysqli->prepare("SELECT cli_senha FROM cliente WHERE cli_cod = ? LIMIT 1")) {
            $stmt->bind_param('i', $cod); // Atribui "$user_id" ao parâmetro
            $stmt->execute(); // Executa a tarefa atribuía
            $stmt->store_result();

            if ($stmt->num_rows == 1) { // Caso o usuário exista
                $stmt->bind_result($password); // pega variáveis a partir do resultado
                $stmt->fetch();
                $login_check = hash('sha512', $password . $ip_address . $user_browser);
                if ($login_check == $login_string) {
                    // Logado!!!
                    return true;
                } else {
                    // Não foi logado
                    return false;
                }
            } else {
                // Não foi logado
                return false;
            }
        } else {
            // Não foi logado
            return false;
        }
    } else {
        // Não foi logado
        return false;
    }
}

function sec_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(172800);
        session_start();
    }
}
function isLogged() {
    if (isset($_SESSION['auth'])) {
        return true;
    } else {
        return false;
    }
}

function isGS1Barcode($barcode) {

    // Remover espaços em branco e caracteres não numéricos
    $barcode = preg_replace('/[^0-9]/', '', $barcode);

    // Verificar o comprimento do código
    if (strlen($barcode) <= 0) {
        return false;
    }

    return true;

}

function montaUrlGS1($site_url_rastro,$cod_rastreio,$cod_barras_produto,$lote,$et_validade){
    $url_gs1 = $site_url_rastro.'/'.$cod_rastreio;
    if(isGS1Barcode($cod_barras_produto)){
        $url_gs1 = $url_gs1.'/01/'.$cod_barras_produto;
        if($lote != ''){
            $url_gs1 = $url_gs1.'/10/'.$lote;
            if($et_validade != NULL && $et_validade != "0000-00-00"){ 
                $url_gs1 = $url_gs1.'?17='.date('ymd',strtotime($et_validade)); 
            }
        } else {
            if($et_validade != NULL && $et_validade != "0000-00-00"){ 
                $url_gs1 = $url_gs1.'?17='.date('ymd',strtotime($et_validade)); 
            }
        }
    }
    return $url_gs1;
}

function verificaSessaoExpirada($tempo_inatividade = 1800) {
    if (isset($_SESSION['ultimo_acesso'])) {
        $tempo_sessao = time() - $_SESSION['ultimo_acesso'];
        if ($tempo_sessao > $tempo_inatividade) {
            session_unset();
            session_destroy();
            header("Location: ../index.php?msg=expirada");
            exit();
        }
    }
    $_SESSION['ultimo_acesso'] = time();
}