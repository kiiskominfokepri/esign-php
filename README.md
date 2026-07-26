# esign-php

[![CI](https://github.com/kiiskominfokepri/esign-php/actions/workflows/ci.yml/badge.svg)](https://github.com/kiiskominfokepri/esign-php/actions/workflows/ci.yml)

PHP client library for **BSrE (Balai Sertifikasi Elektronik / BSSN) Esign Client Service API** v1 and v2.

Based on *Petunjuk Teknis Penggunaan API Esign Client Service v2.2.1*.

## Requirements

- PHP 8.0+
- ext-json, ext-fileinfo
- Composer

## Installation

The package is published on GitHub (public). Until it is listed on Packagist, require it via VCS:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/kiiskominfokepri/esign-php"
    }
  ],
  "require": {
    "kiiskominfokepri/esign-php": "^1.0"
  }
}
```

Then run:

```bash
composer update kiiskominfokepri/esign-php
```

Or in one step (if the repository is already declared):

```bash
composer require kiiskominfokepri/esign-php:^1.0
```

When the package is available on [Packagist](https://packagist.org), you can omit the `repositories` block and use:

```bash
composer require kiiskominfokepri/esign-php
```

For local path development:

```bash
composer install
```

## Authentication

Both V1 and V2 use **HTTP Basic Auth** with credentials issued by the Esign Client Service administrator (`username` / `password`).

Signer identity uses **NIK** and/or **email**, plus **passphrase** or **TOTP** (V2).

## Quick start

### Factory

```php
use KiisKepri\Esign\EsignFactory;

$v1 = EsignFactory::v1($baseUrl, $username, $password);
$v2 = EsignFactory::v2($baseUrl, $username, $password);

// or
$client = EsignFactory::create('v2', $baseUrl, $username, $password);
```

### V1 — invisible sign

```php
use KiisKepri\Esign\EsignFactory;

$esign = EsignFactory::v1($baseUrl, $username, $password)
    ->setNIK('3200xxxxxxxxxxxx');

$response = $esign->signInvisible($passphrase, '/path/to/document.pdf');

if ($response->isSuccess()) {
    $response->saveToFile('/path/to/signed.pdf');
    $idDokumen = $response->getDocumentId(); // from response header when present
} else {
    var_dump($response->getErrors());
}
```

### V1 — visible sign (image / QR / tag)

```php
use KiisKepri\Esign\DTO\VisibleSignOptions;
use KiisKepri\Esign\EsignFactory;

$esign = EsignFactory::v1($baseUrl, $username, $password)->setNIK($nik);

// Image TTD
$options = VisibleSignOptions::withImage('/path/ttd.png', 1, 100, 100, 150, 50)
    ->withReason('Persetujuan')
    ->withLocation('Tanjungpinang');

// QR
// $options = VisibleSignOptions::withQr('https://example.go.id/verify/123', 1, 50, 50, 80, 80);

// Tag in PDF (# or $)
// $options = VisibleSignOptions::withTag('#ttd');

$response = $esign->signVisible($passphrase, '/path/doc.pdf', $options);
```

### V1 — download & verify

```php
$esign->downloadDocument($idDokumen, '/path/downloaded.pdf');

$verify = $esign->signVerification('/path/signed.pdf');
echo $verify->getDocumentName();
echo $verify->getSignatureCounts();
foreach ($verify->getDetails() as $detail) {
    echo $detail->getSignerName();
}
```

### V2 — invisible / bulk / visible

```php
use KiisKepri\Esign\DTO\SignatureProperties;
use KiisKepri\Esign\EsignFactory;

$esign = EsignFactory::v2($baseUrl, $username, $password)
    ->setNIK($nik)
    ->setEmail($email);

// Single invisible
$response = $esign->signInvisible($passphrase, '/path/doc.pdf');
$response->saveToFile('/path/signed.pdf');

// Bulk
$response = $esign->signInvisibleMultiple($passphrase, [
    '/path/a.pdf' => '/path/out-a.pdf',
    '/path/b.pdf' => '/path/out-b.pdf',
]);
$response->saveAll();

// Visible
$props = SignatureProperties::visible('/path/ttd.png', 1, 100, 100, 150, 50);
$response = $esign->signVisible($passphrase, '/path/doc.pdf', $props);

// Low-level (multiple files + custom properties + TOTP)
$response = $esign->sign(
    ['/path/a.pdf', '/path/b.pdf'],
    [SignatureProperties::invisible()],
    passphrase: $passphrase,
    totp: $totp // optional instead of passphrase
);
```

### V2 — TOTP, user status, seal

```php
// Request sign OTP (sent to email)
$esign->requestSignTotp(fileCount: 1);

// Certificate status (only ISSUE can sign)
$status = $esign->checkUserStatus();
if ($status->canSign()) {
    // ...
}

// Register user (path: POST /api/v2/user/register)
$esign->registerUser('Nama Lengkap', 'user@example.go.id');

// Electronic seal
$esign->requestSealActivation($idSubscriber);
$esign->requestSealTotp($idSubscriber, 1, $activationTotp);
$esign->sealPdf($idSubscriber, $sealTotp, ['/path/doc.pdf'], [
    SignatureProperties::invisible(),
]);
$esign->revokeSealActivation($idSubscriber, $totp);
```

### V2 — verify

```php
$verify = $esign->signVerification('/path/signed.pdf', $pdfPassword);
echo $verify->getSummary(); // conclusion
echo $verify->getDocumentName();
```

## API coverage (juknis v2.2.1)

| Area | Endpoint | Client method |
|------|----------|---------------|
| V1 sign | `POST /api/sign/pdf` | `signInvisible`, `signVisible`, `sign` |
| V1 download | `GET /api/sign/download/{id}` | `downloadDocument`, `downloadDocumentBinary` |
| V1 verify | `POST /api/sign/verify` | `signVerification` |
| V1 user status | `GET /api/user/status/{nik}` | `checkUserStatus` |
| V2 sign | `POST /api/v2/sign/pdf` | `sign`, `signInvisible`, `signVisible`, `signInvisibleMultiple` |
| V2 TOTP | `POST /api/v2/sign/get/totp` | `requestSignTotp` |
| V2 verify | `POST /api/v2/verify/pdf` | `signVerification` |
| V2 user status | `POST /api/v2/user/check/status` | `checkUserStatus` |
| V2 register | `POST /api/v2/user/register` | `registerUser` |
| V2 seal | `/api/v2/seal/*` | `requestSealActivation`, `revokeSealActivation`, `requestSealTotp`, `sealPdf` |

> **Note:** `registerUser` uses `/api/v2/user/register`. If your BSrE deployment uses a different path, open an issue or override via a custom HTTP client.

## Examples

```bash
cp examples/.env.example examples/.env
# edit credentials

php examples/v1/sign_document.php
php examples/v2/sign_document.php
php examples/v2/check_status.php
```

Never commit real credentials. Examples load from environment / `examples/.env` only.

## Error handling

- HTTP client uses `http_errors => false` so API error bodies are returned as response objects.
- Transport failures throw `KiisKepri\Esign\Exception\ApiException`.
- Missing files throw `KiisKepri\Esign\Exception\FileNotFoundException`.
- Invalid arguments throw `KiisKepri\Esign\Exception\InvalidArgumentException`.

```php
if (!$response->isSuccess()) {
    // HTTP status + payload
    $response->getStatus();
    $response->getErrors();
    $response->getRawBody();
}
```

## Testing

```bash
composer install
composer test
```

Tests use Guzzle `MockHandler` (no live BSrE calls).

## License

Proprietary — KIIS Kominfo Kepri.
