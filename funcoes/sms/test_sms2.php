<?php
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../configuracao/aws.php';

use Aws\Sns\SnsClient;

$sns = new SnsClient([
    'region' => AWS_REGION,
    'version' => 'latest',
    'credentials' => [
        'key'    => AWS_KEY,
        'secret' => AWS_SECRET
    ]
]);

try {
    $result = $sns->publish([
        'Message' => "🔔 Teste Frutag via sistema!\nSeu SMS via PHP está funcionando.",
        'PhoneNumber' => '+5548988276272',
        'MessageAttributes' => [
            'AWS.SNS.SMS.SMSType' => [
                'DataType' => 'String',
                'StringValue' => 'Transactional'
            ]
        ]
    ]);

    echo "SMS enviado com sucesso!\n";
    echo "Message ID: " . $result['MessageId'] . "\n";

} catch (Exception $e) {
    echo "Erro ao enviar SMS: " . $e->getMessage();
}
