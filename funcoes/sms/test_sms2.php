<?php
require __DIR__ . '/../../vendor/autoload.php';

use Aws\PinpointSmsVoiceV2\PinpointSmsVoiceV2Client;

$client = new PinpointSmsVoiceV2Client([
    'region' => 'us-east-1',
    'version' => 'latest',
    'credentials' => [
        'key'    => getenv('AWS_KEY'),
        'secret' => getenv('AWS_SECRET'),
    ]
]);

try {
    $result = $client->sendTextMessage([
        'DestinationPhoneNumber' => '+554999346368',
        'MessageBody' => 'Teste via Pinpoint SMS (AWS End User Messaging)!',
        'OriginationIdentity' => null, // opcional
        'MessageType' => 'TRANSACTIONAL',
    ]);

    echo "SMS enviado!\n";
    print_r($result);

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
