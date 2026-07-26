<?php

namespace KiisKepri\Esign\V2;

use KiisKepri\Esign\Client\AbstractClient;
use KiisKepri\Esign\DTO\SignatureProperties;
use KiisKepri\Esign\Exception\InvalidArgumentException;
use KiisKepri\Esign\Response\JsonResponse;
use KiisKepri\Esign\Support\FileHelper;

class Esign extends AbstractClient
{
    public function signInvisible(
        string $passphrase,
        string $filePath,
        array $extra = []
    ): SignResponse {
        $props = SignatureProperties::invisible(
            $extra['reason'] ?? null,
            $extra['location'] ?? null,
            $extra['pdfPassword'] ?? null
        );

        return $this->signPdf(
            [$filePath],
            [$props],
            $passphrase,
            $extra['totp'] ?? null,
            $extra['nik'] ?? null,
            $extra['email'] ?? null
        );
    }

    public function signVisible(
        string $passphrase,
        string $filePath,
        SignatureProperties $properties,
        array $extra = []
    ): SignResponse {
        return $this->signPdf(
            [$filePath],
            [$properties],
            $passphrase,
            $extra['totp'] ?? null,
            $extra['nik'] ?? null,
            $extra['email'] ?? null
        );
    }

    /**
     * @param array<string, string> $fileMap input path => output path
     */
    public function signInvisibleMultiple(
        string $passphrase,
        array $fileMap,
        array $extra = []
    ): SignResponse {
        if ($fileMap === []) {
            throw new InvalidArgumentException('fileMap must not be empty');
        }

        $inputs = array_keys($fileMap);
        $outputs = array_values($fileMap);

        $props = SignatureProperties::invisible(
            $extra['reason'] ?? null,
            $extra['location'] ?? null,
            $extra['pdfPassword'] ?? null
        );

        $response = $this->signPdf(
            $inputs,
            [$props],
            $passphrase,
            $extra['totp'] ?? null,
            $extra['nik'] ?? null,
            $extra['email'] ?? null
        );

        return $response->setOutputMap($outputs);
    }

    /**
     * @param list<string> $filePaths
     * @param list<SignatureProperties|array<string, mixed>> $signatureProperties
     */
    public function sign(
        array $filePaths,
        array $signatureProperties,
        ?string $passphrase = null,
        ?string $totp = null,
        ?string $nik = null,
        ?string $email = null
    ): SignResponse {
        return $this->signPdf($filePaths, $signatureProperties, $passphrase, $totp, $nik, $email);
    }

    /**
     * @param list<string> $filePaths
     * @param list<SignatureProperties|array<string, mixed>> $signatureProperties
     */
    private function signPdf(
        array $filePaths,
        array $signatureProperties,
        ?string $passphrase,
        ?string $totp,
        ?string $nik,
        ?string $email
    ): SignResponse {
        if ($filePaths === []) {
            throw new InvalidArgumentException('At least one PDF file is required');
        }

        $resolvedNik = $nik ?? $this->nik;
        $resolvedEmail = $email ?? $this->email;

        if (($resolvedNik === null || $resolvedNik === '') && ($resolvedEmail === null || $resolvedEmail === '')) {
            throw new InvalidArgumentException('Either NIK or email is required for V2 signing');
        }

        if (($passphrase === null || $passphrase === '') && ($totp === null || $totp === '')) {
            throw new InvalidArgumentException('Either passphrase or totp is required for V2 signing');
        }

        $files = [];
        foreach ($filePaths as $path) {
            $files[] = FileHelper::toBase64($path);
        }

        $payload = [
            'signatureProperties' => SignatureProperties::normalizeList($signatureProperties),
            'file' => $files,
        ];

        if ($resolvedNik !== null && $resolvedNik !== '') {
            $payload['nik'] = $resolvedNik;
        }

        if ($resolvedEmail !== null && $resolvedEmail !== '') {
            $payload['email'] = $resolvedEmail;
        }

        if ($passphrase !== null && $passphrase !== '') {
            $payload['passphrase'] = $passphrase;
        }

        if ($totp !== null && $totp !== '') {
            $payload['totp'] = $totp;
        }

        $response = $this->post('/api/v2/sign/pdf', [
            'json' => $payload,
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        ]);

        return new SignResponse($response);
    }

    public function requestSignTotp(?string $nik = null, ?string $email = null, int $fileCount = 1): JsonResponse
    {
        $resolvedNik = $nik ?? $this->nik;
        $resolvedEmail = $email ?? $this->email;

        if (($resolvedNik === null || $resolvedNik === '') && ($resolvedEmail === null || $resolvedEmail === '')) {
            throw new InvalidArgumentException('Either NIK or email is required to request sign TOTP');
        }

        $payload = [
            'data' => $fileCount,
        ];

        if ($resolvedNik !== null && $resolvedNik !== '') {
            $payload['nik'] = $resolvedNik;
        }

        if ($resolvedEmail !== null && $resolvedEmail !== '') {
            $payload['email'] = $resolvedEmail;
        }

        $response = $this->post('/api/v2/sign/get/totp', [
            'json' => $payload,
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        ]);

        return new JsonResponse($response);
    }

    public function checkUserStatus(?string $nik = null, ?string $email = null): UserStatusResponse
    {
        $resolvedNik = $nik ?? $this->nik;
        $resolvedEmail = $email ?? $this->email;

        if (($resolvedNik === null || $resolvedNik === '') && ($resolvedEmail === null || $resolvedEmail === '')) {
            throw new InvalidArgumentException('Either NIK or email is required to check user status');
        }

        $payload = [];
        if ($resolvedNik !== null && $resolvedNik !== '') {
            $payload['nik'] = $resolvedNik;
        }
        if ($resolvedEmail !== null && $resolvedEmail !== '') {
            $payload['email'] = $resolvedEmail;
        }

        $response = $this->post('/api/v2/user/check/status', [
            'json' => $payload,
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        ]);

        return new UserStatusResponse($response);
    }

    public function registerUser(string $nama, string $email): JsonResponse
    {
        $response = $this->post('/api/v2/user/register', [
            'json' => [
                'nama' => $nama,
                'email' => $email,
            ],
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        ]);

        return new JsonResponse($response);
    }

    public function requestSealActivation(string $idSubscriber, ?string $totp = null): JsonResponse
    {
        $payload = ['idSubscriber' => $idSubscriber];
        if ($totp !== null && $totp !== '') {
            $payload['totp'] = $totp;
        }

        $response = $this->post('/api/v2/seal/get/activation', [
            'json' => $payload,
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        ]);

        return new JsonResponse($response);
    }

    public function revokeSealActivation(string $idSubscriber, string $totp): JsonResponse
    {
        $response = $this->post('/api/v2/seal/revoke/activation', [
            'json' => [
                'idSubscriber' => $idSubscriber,
                'totp' => $totp,
            ],
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        ]);

        return new JsonResponse($response);
    }

    public function requestSealTotp(string $idSubscriber, int $fileCount, string $totp): JsonResponse
    {
        $response = $this->post('/api/v2/seal/get/totp', [
            'json' => [
                'idSubscriber' => $idSubscriber,
                'data' => $fileCount,
                'totp' => $totp,
            ],
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        ]);

        return new JsonResponse($response);
    }

    /**
     * @param list<string> $filePaths
     * @param list<SignatureProperties|array<string, mixed>> $signatureProperties
     */
    public function sealPdf(
        string $idSubscriber,
        string $totp,
        array $filePaths,
        array $signatureProperties
    ): SignResponse {
        if ($filePaths === []) {
            throw new InvalidArgumentException('At least one PDF file is required for seal');
        }

        $files = [];
        foreach ($filePaths as $path) {
            $files[] = FileHelper::toBase64($path);
        }

        $payload = [
            'idSubscriber' => $idSubscriber,
            'totp' => $totp,
            'signatureProperties' => SignatureProperties::normalizeList($signatureProperties),
            'file' => $files,
        ];

        $response = $this->post('/api/v2/seal/pdf', [
            'json' => $payload,
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        ]);

        return new SignResponse($response);
    }

    public function signVerification(string $filePath, ?string $pdfPassword = null): VerifyResponse
    {
        $payload = [
            'file' => FileHelper::toBase64($filePath),
        ];

        if ($pdfPassword !== null && $pdfPassword !== '') {
            $payload['password'] = $pdfPassword;
        }

        $response = $this->post('/api/v2/verify/pdf', [
            'json' => $payload,
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        ]);

        return new VerifyResponse($response);
    }
}
