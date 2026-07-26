<?php

require_once __DIR__ . '/../bootstrap.php';

use KiisKepri\Esign\EsignFactory;

$env = esign_require_env('ESIGN_BASE_URL', 'ESIGN_USERNAME', 'ESIGN_PASSWORD');

$esign = EsignFactory::v2($env['ESIGN_BASE_URL'], $env['ESIGN_USERNAME'], $env['ESIGN_PASSWORD'])
    ->setNIK(esign_env('ESIGN_NIK'))
    ->setEmail(esign_env('ESIGN_EMAIL'));

$response = $esign->checkUserStatus();

if ($response->isSuccess()) {
    echo 'Status: ' . ($response->getUserStatus() ?? 'unknown') . PHP_EOL;
    echo 'Can sign: ' . ($response->canSign() ? 'yes' : 'no') . PHP_EOL;
    echo 'Raw: ' . json_encode($response->getData(), JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

fwrite(STDERR, 'Error: ' . json_encode($response->getErrors()) . PHP_EOL);
exit(1);
