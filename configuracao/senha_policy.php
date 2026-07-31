<?php
declare(strict_types=1);

const SENHA_MIN_LENGTH = 8;

function senhaValidarPolitica(string $senha): ?string
{
    if (strlen($senha) < SENHA_MIN_LENGTH) {
        return 'A senha deve ter pelo menos ' . SENHA_MIN_LENGTH . ' caracteres.';
    }
    if (!preg_match('/[a-z]/', $senha) || !preg_match('/[A-Z]/', $senha)) {
        return 'A senha deve conter letras maiúsculas e minúsculas.';
    }
    if (!preg_match('/\d/', $senha)) {
        return 'A senha deve conter pelo menos um número.';
    }

    return null;
}

function senhaPoliticaDescricaoCurta(): string
{
    return 'Mín. ' . SENHA_MIN_LENGTH . ' caracteres, com maiúscula, minúscula e número';
}
