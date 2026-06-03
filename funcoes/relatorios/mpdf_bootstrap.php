<?php
declare(strict_types=1);

/**
 * Retorna pasta temporária gravável para o mPDF (cria subpasta mpdf/ se necessário).
 */
function cadernoMpdfTempDir(): string
{
    $projectRoot = dirname(__DIR__, 2);
    $candidates = [
        $projectRoot . '/tmp/mpdf',
        rtrim(sys_get_temp_dir(), '/\\') . '/caderno-mpdf',
    ];

    foreach ($candidates as $base) {
        $base = str_replace('\\', '/', $base);
        $dirs = [$base, $base . '/mpdf'];

        $ok = true;
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                    $ok = false;
                    break;
                }
            }
            if (!is_writable($dir)) {
                @chmod($dir, 0777);
            }
            if (!is_writable($dir)) {
                $ok = false;
                break;
            }
        }

        if ($ok && is_writable($base)) {
            return $base;
        }
    }

    throw new RuntimeException(
        'Pasta temporária do PDF sem permissão de escrita. ' .
        'Ajuste permissões em tmp/mpdf no servidor (chmod 775 ou 777).'
    );
}
