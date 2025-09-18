<?php
$senha_digitada = "123@Mudar";
$salt = "806913595120198e7c79edcf9227da4166e7e594b8d0043c99a5ade8c75a0fd35ece33eb0a012b69d3485edf30eb3c0ad3f7870d7414c7c15d8c4d07e4bc609e";
$hash_banco = "a19ac8b776a425c8be27a26568556c6ac930e5117bc7528180cbc54f1af58bd8b153ea0073f246b0cac24d8aa683e7f3e1f9f1beb1b7d5fb2cddad90412067c0";

$tests = [
    'senha.salt' => hash('sha512', $senha_digitada . $salt),
    'salt.senha' => hash('sha512', $salt . $senha_digitada),
    'hash(senha).salt' => hash('sha512', hash('sha512', $senha_digitada) . $salt),
    'hash(senha.salt)' => hash('sha512', hash('sha512', $senha_digitada . $salt)),
    'senha' => hash('sha512', $senha_digitada),
    'salt' => hash('sha512', $salt),
];

foreach ($tests as $desc => $result) {
    echo "$desc: $result";
    if ($result === $hash_banco) {
        echo "  <--- MATCH!";
    }
    echo "\n";
}
