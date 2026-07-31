<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../configuracao/aws.php';

use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;

function enviarSMS(string $telefone, string $mensagem): bool
{
    if (!AWS_KEY || !AWS_SECRET || !AWS_REGION) {
        error_log('[SMS] Credenciais AWS não configuradas (AWS_KEY, AWS_SECRET, AWS_REGION).');
        return false;
    }

    try {
        $sns = new SnsClient([
            'version' => 'latest',
            'region'  => AWS_REGION,
            'credentials' => [
                'key'    => AWS_KEY,
                'secret' => AWS_SECRET,
            ],
        ]);

        $sns->publish([
            'Message'     => $mensagem,
            'PhoneNumber' => $telefone,
            'MessageAttributes' => [
                'AWS.SNS.SMS.SMSType' => [
                    'DataType' => 'String',
                    'StringValue' => 'Transactional',
                ],
            ],
        ]);

        return true;

    } catch (AwsException $e) {
        error_log('[SMS] Erro AWS: ' . $e->getAwsErrorMessage());
        return false;
    }
}
