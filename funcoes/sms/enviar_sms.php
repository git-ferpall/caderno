<?php
use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;

function enviarSMS(string $telefone, string $mensagem): bool
{
    try {
        $sns = new SnsClient([
            'version' => 'latest',
            'region'  => getenv('AWS_REGION'),
            'credentials' => [
                'key'    => getenv('AWS_KEY'),
                'secret' => getenv('AWS_SECRET'),
            ],
        ]);

        $sns->publish([
            'Message'     => $mensagem,
            'PhoneNumber' => $telefone,
        ]);

        return true;

    } catch (AwsException $e) {
        error_log('[SMS] ' . $e->getAwsErrorMessage());
        return false;
    }
}
