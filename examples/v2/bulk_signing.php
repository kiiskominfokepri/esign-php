<?php

require_once __DIR__ . '/../bootstrap.php';

use KiisKepri\Esign\EsignFactory;

$env = esign_require_env(
    'ESIGN_BASE_URL',
    'ESIGN_USERNAME',
    'ESIGN_PASSWORD',
    'ESIGN_NIK',
    'ESIGN_EMAIL',
    'ESIGN_PASSPHRASE'
);

$esign = EsignFactory::v2($env['ESIGN_BASE_URL'], $env['ESIGN_USERNAME'], $env['ESIGN_PASSWORD'])
    ->setNIK($env['ESIGN_NIK'])
    ->setEmail($env['ESIGN_EMAIL']);

$fileMap = [
    __DIR__ . '/../../storage/TEST-TTE-ESIGN.pdf' => __DIR__ . '/../../storage/signed/v2-bulk-1.pdf',
    __DIR__ . '/../../storage/v2-unsigned.pdf' => __DIR__ . '/../../storage/signed/v2-bulk-2.pdf',
];

$response = $esign->signInvisibleMultiple($env['ESIGN_PASSPHRASE'], $fileMap);

if ($response->isSuccess()) {
    $saved = $response->saveAll();
    echo 'Bulk signed: ' . implode(', ', $saved) . PHP_EOL;
    exit(0);
}

fwrite(STDERR, 'Error: ' . json_encode($response->getErrors()) . PHP_EOL);
exit(1);
