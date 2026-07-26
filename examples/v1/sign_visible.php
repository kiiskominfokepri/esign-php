<?php

require_once __DIR__ . '/../bootstrap.php';

use KiisKepri\Esign\DTO\VisibleSignOptions;
use KiisKepri\Esign\EsignFactory;

$env = esign_require_env('ESIGN_BASE_URL', 'ESIGN_USERNAME', 'ESIGN_PASSWORD', 'ESIGN_NIK', 'ESIGN_PASSPHRASE');

$esign = EsignFactory::v1($env['ESIGN_BASE_URL'], $env['ESIGN_USERNAME'], $env['ESIGN_PASSWORD'])
    ->setNIK($env['ESIGN_NIK']);

$filePath = __DIR__ . '/../../storage/TEST-TTE-ESIGN.pdf';
$imagePath = esign_env('ESIGN_TTD_IMAGE', __DIR__ . '/../../storage/ttd.png');
$savePath = __DIR__ . '/../../storage/signed/v1-signed-visible.pdf';

if (!is_file($imagePath)) {
    fwrite(STDERR, "TTD image not found: {$imagePath}" . PHP_EOL);
    fwrite(STDERR, "Set ESIGN_TTD_IMAGE or place ttd.png under storage/." . PHP_EOL);
    exit(1);
}

$options = VisibleSignOptions::withImage($imagePath, 1, 100, 100, 150, 50)
    ->withReason('Persetujuan dokumen')
    ->withLocation('Tanjungpinang');

$response = $esign->signVisible($env['ESIGN_PASSPHRASE'], $filePath, $options);

if ($response->isSuccess()) {
    $response->saveToFile($savePath);
    echo "Visible signed OK: {$savePath}" . PHP_EOL;
    exit(0);
}

fwrite(STDERR, 'Error: ' . json_encode($response->getErrors()) . PHP_EOL);
exit(1);
