<?php

namespace KiisKepri\Esign\V1;

use KiisKepri\Esign\Client\AbstractClient;
use KiisKepri\Esign\DTO\VisibleSignOptions;
use KiisKepri\Esign\Exception\InvalidArgumentException;
use KiisKepri\Esign\Response\JsonResponse;
use KiisKepri\Esign\Support\FileHelper;

class Esign extends AbstractClient
{
    public function signInvisible(
        string $passphrase,
        string $filePath,
        ?string $fileName = null,
        ?string $nik = null
    ): SignResponse {
        return $this->signPdf($passphrase, $filePath, $fileName, 'invisible', null, $nik);
    }

    public function signVisible(
        string $passphrase,
        string $filePath,
        VisibleSignOptions $options,
        ?string $fileName = null,
        ?string $nik = null
    ): SignResponse {
        return $this->signPdf($passphrase, $filePath, $fileName, 'visible', $options, $nik);
    }

    public function sign(
        string $passphrase,
        string $filePath,
        string $tampilan = 'invisible',
        ?VisibleSignOptions $options = null,
        ?string $fileName = null,
        ?string $nik = null
    ): SignResponse {
        $tampilan = strtolower($tampilan);
        if (!in_array($tampilan, ['invisible', 'visible'], true)) {
            throw new InvalidArgumentException('tampilan must be "invisible" or "visible"');
        }

        if ($tampilan === 'visible' && $options === null) {
            throw new InvalidArgumentException('VisibleSignOptions is required when tampilan=visible');
        }

        return $this->signPdf($passphrase, $filePath, $fileName, $tampilan, $options, $nik);
    }

    private function signPdf(
        string $passphrase,
        string $filePath,
        ?string $fileName,
        string $tampilan,
        ?VisibleSignOptions $options,
        ?string $nik
    ): SignResponse {
        $resolvedNik = $nik ?? $this->nik;
        if ($resolvedNik === null || $resolvedNik === '') {
            throw new InvalidArgumentException('NIK is required. Call setNIK() or pass $nik.');
        }

        FileHelper::assertReadable($filePath);
        $name = FileHelper::basename($filePath, $fileName);

        $multipart = [
            ['name' => 'nik', 'contents' => $resolvedNik],
            ['name' => 'passphrase', 'contents' => $passphrase],
            ['name' => 'tampilan', 'contents' => $tampilan],
            [
                'name' => 'file',
                'contents' => FileHelper::openReadStream($filePath),
                'filename' => $name,
                'headers' => ['Content-Type' => 'application/pdf'],
            ],
        ];

        if ($tampilan === 'visible' && $options !== null) {
            $multipart = array_merge($multipart, $options->toMultipart());
        }

        $response = $this->post('/api/sign/pdf', [
            'multipart' => $multipart,
        ]);

        return new SignResponse($response);
    }

    public function downloadDocument(string $idDokumen, string $savePath): bool
    {
        $response = $this->get('/api/sign/download/' . rawurlencode($idDokumen));

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $dir = dirname($savePath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        return file_put_contents($savePath, (string) $response->getBody()) !== false;
    }

    public function downloadDocumentBinary(string $idDokumen): SignResponse
    {
        $response = $this->get('/api/sign/download/' . rawurlencode($idDokumen));
        return new SignResponse($response);
    }

    public function signVerification(string $filePath, ?string $fileName = null): VerifyResponse
    {
        FileHelper::assertReadable($filePath);
        $name = FileHelper::basename($filePath, $fileName);

        $response = $this->post('/api/sign/verify', [
            'multipart' => [
                [
                    'name' => 'signed_file',
                    'contents' => FileHelper::openReadStream($filePath),
                    'filename' => $name,
                    'headers' => ['Content-Type' => 'application/pdf'],
                ],
            ],
        ]);

        return new VerifyResponse($response);
    }

    public function checkUserStatus(string $nik): JsonResponse
    {
        $response = $this->get('/api/user/status/' . rawurlencode($nik));
        return new JsonResponse($response);
    }
}
