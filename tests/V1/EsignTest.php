<?php

namespace KiisKepri\Esign\Tests\V1;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use KiisKepri\Esign\DTO\VisibleSignOptions;
use KiisKepri\Esign\Exception\FileNotFoundException;
use KiisKepri\Esign\Exception\InvalidArgumentException;
use KiisKepri\Esign\V1\Esign;
use PHPUnit\Framework\TestCase;

class EsignTest extends TestCase
{
    private string $samplePdf;

    protected function setUp(): void
    {
        $this->samplePdf = sys_get_temp_dir() . '/esign-test-' . uniqid('', true) . '.pdf';
        file_put_contents($this->samplePdf, '%PDF-1.4 test');
    }

    protected function tearDown(): void
    {
        if (is_file($this->samplePdf)) {
            unlink($this->samplePdf);
        }
    }

    private function client(MockHandler $mock, array &$history = []): Esign
    {
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $http = new Client([
            'handler' => $stack,
            'http_errors' => false,
        ]);

        return new Esign('https://esign.test', 'user', 'pass', [], $http);
    }

    public function testSignInvisibleSuccessStoresPdfAndDocumentId(): void
    {
        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['id_dokumen' => 'DOC-123', 'Content-Type' => 'application/pdf'], '%PDF-1.4 signed'),
        ]), $history)->setNIK('3200123456789012');

        $response = $esign->signInvisible('secret', $this->samplePdf);

        $this->assertTrue($response->isSuccess());
        $this->assertSame('DOC-123', $response->getDocumentId());
        $this->assertSame('%PDF-1.4 signed', $response->getBinary());

        $out = sys_get_temp_dir() . '/esign-out-' . uniqid('', true) . '.pdf';
        $this->assertTrue($response->saveToFile($out));
        $this->assertSame('%PDF-1.4 signed', file_get_contents($out));
        unlink($out);

        $this->assertCount(1, $history);
        $this->assertStringContainsString('/api/sign/pdf', (string) $history[0]['request']->getUri());
    }

    public function testSignRequiresNik(): void
    {
        $esign = $this->client(new MockHandler([]));

        $this->expectException(InvalidArgumentException::class);
        $esign->signInvisible('secret', $this->samplePdf);
    }

    public function testSignMissingFile(): void
    {
        $esign = $this->client(new MockHandler([]))->setNIK('3200123456789012');

        $this->expectException(FileNotFoundException::class);
        $esign->signInvisible('secret', '/tmp/does-not-exist-' . uniqid('', true) . '.pdf');
    }

    public function testSignVisibleWithImage(): void
    {
        $image = sys_get_temp_dir() . '/esign-ttd-' . uniqid('', true) . '.png';
        file_put_contents($image, 'PNG');

        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/pdf'], '%PDF-1.4 visible'),
        ]), $history)->setNIK('3200123456789012');

        $options = VisibleSignOptions::withImage($image, 1, 10, 20, 100, 40)
            ->withReason('OK');

        $response = $esign->signVisible('secret', $this->samplePdf, $options);

        $this->assertTrue($response->isSuccess());
        $this->assertCount(1, $history);

        $body = (string) $history[0]['request']->getBody();
        $this->assertStringContainsString('name="tampilan"', $body);
        $this->assertStringContainsString('visible', $body);
        $this->assertStringContainsString('name="image"', $body);
        $this->assertStringContainsString('name="imageTTD"', $body);

        unlink($image);
    }

    public function testVerifyParsesSnakeCasePayload(): void
    {
        $payload = [
            'nama_dokumen' => 'doc.pdf',
            'jumlah_signature' => 1,
            'summary' => 'OK',
            'notes' => null,
            'details' => [
                [
                    'signature_field' => 'Signature1',
                    'info_signer' => ['signer_name' => 'Budi'],
                    'info_tsa' => ['name' => 'TSA'],
                    'signature_document' => [
                        'document_integrity' => true,
                        'signed_in' => '2024-01-02 10:11:12.000000',
                    ],
                ],
            ],
        ];

        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($payload)),
        ]));

        $response = $esign->signVerification($this->samplePdf);

        $this->assertTrue($response->isSuccess());
        $this->assertSame('doc.pdf', $response->getDocumentName());
        $this->assertSame(1, $response->getSignatureCounts());
        $this->assertSame(['Budi'], $response->getSigners());
        $this->assertTrue($response->getDetails()[0]->getDocumentIntegrity());
    }

    public function testErrorResponseIsNotSuccess(): void
    {
        $esign = $this->client(new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'passphrase salah'])),
        ]))->setNIK('3200123456789012');

        $response = $esign->signInvisible('bad', $this->samplePdf);

        $this->assertFalse($response->isSuccess());
        $this->assertSame('passphrase salah', $response->getErrors());
    }
}
