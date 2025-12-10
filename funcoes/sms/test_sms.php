<?php
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../configuracao/aws.php';


use Aws\Sns\SnsClient;

$awsKey = getenv('AWS_ACCESS_KEY');
$awsSecret = getenv('AWS_SECRET_KEY');


$sns = new SnsClient([
    'region' => 'us-east-1',
    'version' => 'latest',
    'credentials' => [
        'key'    => $awsKey,
        'secret' => $awsSecret
    ]
]);

try {

    $result = $sns->publish([
        'Message' => "🔔 Teste Frutag via sistema!\nSeu SMS via PHP está funcionando.",
        'PhoneNumber' => '+554999346368', // SEU NÚMERO AQUI
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
