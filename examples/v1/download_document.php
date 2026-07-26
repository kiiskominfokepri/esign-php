<?php

require_once __DIR__ . '/../bootstrap.php';

use KiisKepri\Esign\EsignFactory;

$env = esign_require_env('ESIGN_BASE_URL', 'ESIGN_USERNAME', 'ESIGN_PASSWORD');

$idDokumen = $argv[1] ?? esign_env('ESIGN_ID_DOKUMEN');
if ($idDokumen === null || $idDokumen === '') {
    fwrite(STDERR, "Usage: php download_document.php <id_dokumen>" . PHP_EOL);
    exit(1);
}

$esign = EsignFactory::v1($env['ESIGN_BASE_URL'], $env['ESIGN_USERNAME'], $env['ESIGN_PASSWORD']);
$savePath = __DIR__ . '/../../storage/signed/v1-downloaded.pdf';

if ($esign->downloadDocument($idDokumen, $savePath)) {
    echo "Downloaded: {$savePath}" . PHP_EOL;
    exit(0);
}

fwrite(STDERR, "Download failed for id_dokumen={$idDokumen}" . PHP_EOL);
exit(1);
