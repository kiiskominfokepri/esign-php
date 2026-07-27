<?php

namespace KiisKepri\Esign\Tests\V1;

use GuzzleHttp\Psr7\Response;
use KiisKepri\Esign\V1\SignResponse;
use PHPUnit\Framework\TestCase;

class SignResponseTest extends TestCase
{
    public function testPdfBodySuccess(): void
    {
        $response = new SignResponse(new Response(
            200,
            ['Content-Type' => 'application/pdf', 'id_dokumen' => 'DOC-A'],
            '%PDF-1.4 body'
        ));

        $this->assertTrue($response->isSuccess());
        $this->assertSame('%PDF-1.4 body', $response->getBinary());
        $this->assertSame('DOC-A', $response->getDocumentId());
    }

    public function testJsonErrorBody(): void
    {
        $response = new SignResponse(new Response(
            400,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => 'passphrase salah'])
        ));

        $this->assertFalse($response->isSuccess());
        $this->assertSame('passphrase salah', $response->getErrors());
        $this->assertSame('', $response->getBinary());
    }

    public function testDocumentIdHeaderVariants(): void
    {
        $variants = ['id_dokumen', 'id-dokumen', 'Id_Dokumen', 'ID_DOKUMEN'];

        foreach ($variants as $header) {
            $response = new SignResponse(new Response(
                200,
                ['Content-Type' => 'application/pdf', $header => 'ID-' . $header],
                '%PDF-1.4 x'
            ));
            $this->assertSame('ID-' . $header, $response->getDocumentId(), 'Failed for header ' . $header);
        }
    }

    public function testSaveToFileFailsWhenNotSuccess(): void
    {
        $response = new SignResponse(new Response(
            500,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => 'server'])
        ));

        $out = sys_get_temp_dir() . '/esign-v1-sr-' . uniqid('', true) . '.pdf';
        $this->assertFalse($response->saveToFile($out));
        $this->assertFalse(is_file($out));
    }

    public function testJsonErrorOn200IsNotSuccess(): void
    {
        $response = new SignResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => 'logical error'])
        ));

        $this->assertFalse($response->isSuccess());
        $this->assertSame('logical error', $response->getErrors());
    }
}
