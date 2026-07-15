<?php
declare(strict_types=1);

/**
 * Gerador de BR Code PIX estático (padrão EMV®-QRCPS / Banco Central).
 * O payload gerado funciona como "PIX copia-e-cola" e como conteúdo do QR Code.
 */

/** Campo EMV: ID + tamanho (2 dígitos) + valor. */
function frutibankEmv(string $id, string $valor): string
{
    return $id . str_pad((string)strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
}

/** Remove acentos e caracteres fora do padrão EMV, limita o tamanho. */
function frutibankEmvTexto(string $texto, int $max): string
{
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    if ($t === false || $t === '') $t = $texto;
    $t = strtoupper(preg_replace('/[^A-Za-z0-9 .\-]/', ' ', $t) ?? '');
    $t = trim(preg_replace('/\s+/', ' ', $t) ?? '');
    return substr($t, 0, $max);
}

/** CRC16-CCITT (0xFFFF), exigido pelo campo 63 do BR Code. */
function frutibankCrc16(string $data): string
{
    $crc = 0xFFFF;
    $len = strlen($data);
    for ($i = 0; $i < $len; $i++) {
        $crc ^= ord($data[$i]) << 8;
        for ($b = 0; $b < 8; $b++) {
            $crc = ($crc & 0x8000) ? ((($crc << 1) & 0xFFFF) ^ 0x1021) : (($crc << 1) & 0xFFFF);
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

/**
 * Monta o payload PIX estático completo.
 *
 * @param string      $chave     Chave PIX do recebedor (CPF/CNPJ/e-mail/telefone/aleatória)
 * @param string      $nome      Nome do recebedor (será truncado em 25 chars)
 * @param string      $cidade    Cidade do recebedor (será truncada em 15 chars)
 * @param float       $valor     Valor da cobrança (> 0)
 * @param string      $txid      Identificador (A-Z, a-z, 0-9, máx. 25)
 * @param string|null $descricao Descrição curta opcional (vai no campo 26-02)
 */
function frutibankPixPayload(string $chave, string $nome, string $cidade, float $valor, string $txid, ?string $descricao = null): string
{
    $chave = trim($chave);
    $nome = frutibankEmvTexto($nome, 25);
    $cidade = frutibankEmvTexto($cidade, 15);
    $txid = substr(preg_replace('/[^A-Za-z0-9]/', '', $txid) ?? '', 0, 25) ?: '***';

    $conta = frutibankEmv('00', 'br.gov.bcb.pix') . frutibankEmv('01', $chave);
    if ($descricao !== null && trim($descricao) !== '') {
        // limite prático: o campo 26 inteiro não pode passar de 99 caracteres
        $maxDesc = max(0, 99 - strlen($conta) - 4);
        $desc = frutibankEmvTexto($descricao, min(40, $maxDesc));
        if ($desc !== '') {
            $conta .= frutibankEmv('02', $desc);
        }
    }

    $payload = frutibankEmv('00', '01')                                   // Payload Format Indicator
        . frutibankEmv('26', $conta)                                       // Merchant Account Info (PIX)
        . frutibankEmv('52', '0000')                                       // Merchant Category Code
        . frutibankEmv('53', '986')                                        // Moeda: BRL
        . frutibankEmv('54', number_format($valor, 2, '.', ''))            // Valor
        . frutibankEmv('58', 'BR')                                         // País
        . frutibankEmv('59', $nome)                                        // Nome do recebedor
        . frutibankEmv('60', $cidade)                                      // Cidade
        . frutibankEmv('62', frutibankEmv('05', $txid))                    // TXID
        . '6304';                                                          // CRC (placeholder)

    return $payload . frutibankCrc16($payload);
}
