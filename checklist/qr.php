<?php
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

function gerarQRCodeChecklist(string $hash): string
{
    $url = "https://caderno.frutag.com.br/checklist/validar.php?h=".$hash;

    $result = Builder::create()
        ->writer(new PngWriter())
        ->data($url)
        ->size(220)
        ->margin(10)
        ->build();

    // salva como arquivo temporário
    $file = sys_get_temp_dir()."/qr_$hash.png";
    $result->saveToFile($file);

    return $file;
}
