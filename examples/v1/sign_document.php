<?php

require_once __DIR__ . '/../bootstrap.php';

use KiisKepri\Esign\EsignFactory;

$env = esign_require_env('ESIGN_BASE_URL', 'ESIGN_USERNAME', 'ESIGN_PASSWORD', 'ESIGN_NIK', 'ESIGN_PASSPHRASE');

$esign = EsignFactory::v1($env['ESIGN_BASE_URL'], $env['ESIGN_USERNAME'], $env['ESIGN_PASSWORD'])
    ->setNIK($env['ESIGN_NIK']);

$filePath = __DIR__ . '/../../storage/TEST-TTE-ESIGN.pdf';
$savePath = __DIR__ . '/../../storage/signed/v1-signed-test.pdf';

$response = $esign->signInvisible($env['ESIGN_PASSPHRASE'], $filePath);

if ($response->isSuccess()) {
    $response->saveToFile($savePath);
    echo "Signed OK: {$savePath}" . PHP_EOL;
    if ($response->getDocumentId()) {
        echo "id_dokumen: " . $response->getDocumentId() . PHP_EOL;
    }
    exit(0);
}

fwrite(STDERR, 'Error: ' . json_encode($response->getErrors()) . PHP_EOL);
exit(1);
