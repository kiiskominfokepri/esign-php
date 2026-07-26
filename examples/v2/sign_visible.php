<?php

require_once __DIR__ . '/../bootstrap.php';

use KiisKepri\Esign\DTO\SignatureProperties;
use KiisKepri\Esign\EsignFactory;

$env = esign_require_env(
    'ESIGN_BASE_URL',
    'ESIGN_USERNAME',
    'ESIGN_PASSWORD',
    'ESIGN_NIK',
    'ESIGN_EMAIL',
    'ESIGN_PASSPHRASE'
);

$imagePath = esign_env('ESIGN_TTD_IMAGE', __DIR__ . '/../../storage/ttd.png');
if (!is_file($imagePath)) {
    fwrite(STDERR, "TTD image not found: {$imagePath}" . PHP_EOL);
    exit(1);
}

$esign = EsignFactory::v2($env['ESIGN_BASE_URL'], $env['ESIGN_USERNAME'], $env['ESIGN_PASSWORD'])
    ->setNIK($env['ESIGN_NIK'])
    ->setEmail($env['ESIGN_EMAIL']);

$props = SignatureProperties::visible($imagePath, 1, 100, 100, 150, 50)
    ->withReason('Persetujuan dokumen')
    ->withLocation('Tanjungpinang');

$filePath = __DIR__ . '/../../storage/TEST-TTE-ESIGN.pdf';
$savePath = __DIR__ . '/../../storage/signed/v2-signed-visible.pdf';

$response = $esign->signVisible($env['ESIGN_PASSPHRASE'], $filePath, $props);

if ($response->isSuccess()) {
    $response->saveToFile($savePath);
    echo "Visible signed OK: {$savePath}" . PHP_EOL;
    exit(0);
}

fwrite(STDERR, 'Error: ' . json_encode($response->getErrors()) . PHP_EOL);
exit(1);
