<?php

require_once __DIR__ . '/../bootstrap.php';

use KiisKepri\Esign\EsignFactory;

$env = esign_require_env('ESIGN_BASE_URL', 'ESIGN_USERNAME', 'ESIGN_PASSWORD');

$esign = EsignFactory::v2($env['ESIGN_BASE_URL'], $env['ESIGN_USERNAME'], $env['ESIGN_PASSWORD']);
$filePath = $argv[1] ?? __DIR__ . '/../../storage/v2-signed-test.pdf';

$response = $esign->signVerification($filePath);

if ($response->isSuccess()) {
    echo 'Document: ' . $response->getDocumentName() . PHP_EOL;
    echo 'Signatures: ' . $response->getSignatureCounts() . PHP_EOL;
    echo 'Conclusion: ' . $response->getSummary() . PHP_EOL;
    echo 'Signers: ' . implode(', ', array_filter($response->getSigners())) . PHP_EOL;
    exit(0);
}

fwrite(STDERR, 'Error: ' . json_encode($response->getErrors()) . PHP_EOL);
exit(1);
